<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    /**
     * Lấy danh sách sản phẩm (có phân trang, tìm kiếm, lọc theo giá và danh mục).
     * Dành cho cả Trang chủ (lấy sản phẩm đang bán) và Admin (lấy tất cả).
     */
    public function index(Request $request)
    {
        $query = Product::select([
            'id', 'category_id', 'brand_id', 'name', 'slug', 'sku', 'base_price', 'stock_quantity',
            'sale_price', 'image_url', 'is_active', 'is_featured', 'created_at'
        ])->with([
            'category:id,name,slug', 
            'brand:id,name,logo,slug',
            'specifications',
            'variants.color',
            'variants.ram',
            'variants.storage'
        ])->withCount(['reviews' => function($query) {
            $query->where('status', 'approved');
        }])->withAvg(['reviews' => function($query) {
            $query->where('status', 'approved');
        }], 'rating');

        // Admin gửi all=true -> không lọc is_active, trả hết
        // FE public mặc định chỉ hiển thị sản phẩm đang bán
        if (!$request->boolean('all')) {
            $query->where('is_active', true);
        }

        // Lọc theo danh mục (bao gồm cả danh mục con)
        if ($request->filled('category_id')) {
            $allCategoryIds = $this->getAllChildCategoryIds($request->category_id);
            $query->whereIn('category_id', $allCategoryIds);
        }

        // Tìm kiếm theo tên hoặc SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Lọc theo khoảng giá
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Sắp xếp
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['base_price', 'name', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Admin: phân trang truyền thống (10 SP/trang), hiển thị cả SP ẩn
        if ($request->boolean('all')) {
            /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
            $products = $query->orderBy('id')->paginate(100);
            $products->appends($request->only(['category_id', 'min_price', 'max_price', 'sort_by', 'sort_order', 'all']));

            return response()->json(array_merge(['success' => true], $products->toArray()));
        }

        // Luôn thêm orderBy id để cursor pagination không bỏ sót dữ liệu
        $query->orderBy('id');

        // Phân trang bằng cursor pagination (12 sản phẩm mỗi trang)
        /** @var \Illuminate\Pagination\CursorPaginator $products */
        $products = $query->cursorPaginate(12);

        return response()->json(array_merge(['success' => true], $products->toArray()));
    }

    /**
     * Xem chi tiết 1 sản phẩm cụ thể.
     * Trả về thông tin sản phẩm kèm theo bảng biến thể (màu sắc, dung lượng) và đánh giá.
     */
    public function show($id)
    {
        $product = Product::with([
            'category', 
            'brand', 
            'specifications', 
            'variants.color', 
            'variants.ram', 
            'variants.storage', 
            'images'
        ])->withCount(['reviews' => function($query) {
            $query->where('status', 'approved');
        }])->withAvg(['reviews' => function($query) {
            $query->where('status', 'approved');
        }], 'rating')->find($id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Thêm sản phẩm mới (Chỉ dành cho Admin).
     * Xử lý upload ảnh, lưu thông tin cơ bản và tách thông số kỹ thuật lưu vào bảng phone_specifications.
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($request->name) . '-' . time();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validatedData['image_url'] = url('storage/' . $path);
        }

        // Tách các trường thông số kỹ thuật để lưu vào bảng phone_specifications
        $specFields = [
            'screen_size', 'screen_tech', 'rear_camera', 'front_camera', 
            'chipset', 'battery', 'charging_speed', 'operating_system', 'weight_g', 'material'
        ];
        
        $productData = Arr::except($validatedData, $specFields);
        $specData = Arr::only($validatedData, $specFields);

        $product = Product::create($productData);
        $product->specifications()->create($specData);

        // Cập nhật variants nếu có dữ liệu màu/dung lượng gửi lên
        $colors = $request->input('colors', []);
        $storages = $request->input('storages', []);
        
        if (!empty($colors) || !empty($storages)) {
            if (empty($colors)) $colors = [null];
            if (empty($storages)) $storages = [null];

            $totalVariants = count($colors) * count($storages);
            $totalInputStock = (int) ($product->stock_quantity ?? 0);
            $baseStock = $totalVariants > 0 ? (int) floor($totalInputStock / $totalVariants) : 0;
            $remainder = $totalVariants > 0 ? ($totalInputStock % $totalVariants) : 0;

            $isFirst = true;
            foreach ($colors as $cName) {
                $colorId = null;
                if ($cName) {
                    $color = \App\Models\Color::firstOrCreate(['name' => $cName], ['hex_code' => '#000000']);
                    $colorId = $color->id;
                }
                
                foreach ($storages as $sValue) {
                    $storageId = null;
                    if ($sValue) {
                        $storage = \App\Models\StorageOption::firstOrCreate(['value' => $sValue]);
                        $storageId = $storage->id;
                    }

                    $variantStock = $baseStock + ($isFirst ? $remainder : 0);
                    $isFirst = false;

                    $product->variants()->create([
                        'sku' => $product->sku . '-' . Str::slug($cName ?: 'default') . '-' . Str::slug($sValue ?: 'default'),
                        'color_id' => $colorId,
                        'storage_id' => $storageId,
                        'price' => $product->base_price,
                        'stock_quantity' => $variantStock,
                        'is_available' => true,
                    ]);
                }
            }
        }

        // Upload gallery images
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $index => $galleryImage) {
                $path = $galleryImage->store('products_gallery', 'public');
                
                $product->images()->create([
                    'image_url' => url('storage/' . $path),
                    'is_primary' => false,
                    'sort_order' => $index
                ]);
            }
        }

        $product->load('images', 'variants');

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được tạo thành công!',
            'product' => $product
        ], 201);
    }

    /**
     * Cập nhật thông tin sản phẩm (Chỉ dành cho Admin).
     * Xóa ảnh cũ (nếu có cập nhật ảnh mới), cập nhật thông số và tự động sinh các biến thể (variants).
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $validatedData = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old primary image correctly using raw path
            if ($oldUrl = $product->getRawOriginal('image_url')) {
                Storage::disk('public')->delete(str_replace(url('storage') . '/', '', $oldUrl));
            }
            $path = $request->file('image')->store('products', 'public');
            $validatedData['image_url'] = url('storage/' . $path);
        }

        // Tách các trường thông số kỹ thuật để lưu vào bảng phone_specifications
        $specFields = [
            'screen_size', 'screen_tech', 'rear_camera', 'front_camera', 
            'chipset', 'battery', 'charging_speed', 'operating_system', 'weight_g', 'material'
        ];
        
        $productData = Arr::except($validatedData, $specFields);
        $specData = Arr::only($validatedData, $specFields);

        $product->update($productData);
        if (!empty($specData)) {
            $product->specifications()->updateOrCreate(
                ['product_id' => $product->id],
                $specData
            );
        }

        // Cập nhật variants (màu và dung lượng)
        if ($request->has('colors') || $request->has('storages')) {
            $colors = $request->input('colors', []);
            $storages = $request->input('storages', []);

            if (empty($colors)) $colors = [null];
            if (empty($storages)) $storages = [null];

            $keepSkus = [];

            foreach ($colors as $cName) {
                $colorId = null;
                if ($cName) {
                    $color = \App\Models\Color::firstOrCreate(['name' => $cName], ['hex_code' => '#000000']);
                    $colorId = $color->id;
                }
                
                foreach ($storages as $sValue) {
                    $storageId = null;
                    if ($sValue) {
                        $storage = \App\Models\StorageOption::firstOrCreate(['value' => $sValue]);
                        $storageId = $storage->id;
                    }

                    $sku = $product->sku . '-' . Str::slug($cName ?: 'default') . '-' . Str::slug($sValue ?: 'default');
                    $keepSkus[] = $sku;

                    $variant = $product->variants()->withTrashed()->where('sku', $sku)->first();
                    
                    if ($variant) {
                        // Khôi phục biến thể và giữ nguyên số lượng tồn kho của biến thể đã có
                        $variant->restore();
                        $variant->update([
                            'color_id' => $colorId,
                            'storage_id' => $storageId,
                            'price' => $product->base_price,
                            'is_available' => true,
                        ]);
                    } else {
                        // Biến thể mới được thêm khi chỉnh sửa khởi tạo với số lượng tồn kho = 0
                        $product->variants()->create([
                            'sku' => $sku,
                            'color_id' => $colorId,
                            'storage_id' => $storageId,
                            'price' => $product->base_price,
                            'stock_quantity' => 0,
                            'is_available' => true,
                        ]);
                    }
                }
            }

            // Xóa biến thể không còn nằm trong lựa chọn mới
            $product->variants()->whereNotIn('sku', $keepSkus)->delete();

            // Tính lại tổng số lượng tồn kho sản phẩm từ tất cả các biến thể
            $totalVariantStock = $product->variants()->sum('stock_quantity');
            $product->update(['stock_quantity' => $totalVariantStock]);
        }

        if ($request->hasFile('gallery_images')) {
            // Replace all existing gallery images
            foreach ($product->images as $oldImage) {
                if ($oldUrl = $oldImage->getRawOriginal('image_url')) {
                    if (Str::contains($oldUrl, 'res.cloudinary.com')) {
                        // Skip deleting from cloudinary
                    } else {
                        Storage::disk('public')->delete($oldUrl);
                    }
                }
                $oldImage->delete(); // Or permanent delete since the record logic uses soft delete maybe
            }

            foreach ($request->file('gallery_images') as $index => $galleryImage) {
                $path = $galleryImage->store('products_gallery', 'public');
                
                $product->images()->create([
                    'image_url' => url('storage/' . $path),
                    'is_primary' => false,
                    'sort_order' => $index
                ]);
            }
        }

        $product->load('images');

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được cập nhật thành công!',
            'product' => $product
        ]);
    }

    /**
     * Xóa sản phẩm (Chỉ dành cho Admin).
     * Áp dụng Soft Delete (Xóa mềm), sản phẩm chỉ bị ẩn đi chứ không bị xóa vĩnh viễn khỏi Database.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        // Soft delete - chỉ đánh dấu xóa, không xóa hẳn ra khỏi CSDL
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được xóa thành công!'
        ]);
    }

    /**
     * Lấy tất cả ID của danh mục con (đệ quy)
     */
    private function getAllChildCategoryIds($categoryId)
    {
        // 1 query duy nhất lấy tất cả categories (thay vì đệ quy N queries)
        $allCategories = Category::pluck('parent_id', 'id');

        $ids = [(int) $categoryId];
        $queue = [(int) $categoryId];

        // BFS: duyệt breadth-first tìm tất cả danh mục con
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            foreach ($allCategories as $id => $parentId) {
                if ($parentId == $currentId && !in_array($id, $ids)) {
                    $ids[] = $id;
                    $queue[] = $id;
                }
            }
        }

        return $ids;
    }




}
