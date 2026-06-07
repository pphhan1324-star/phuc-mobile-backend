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

/**
 * @OA\Schema(
 *     schema="Product",
 *     title="Product",
 *     description="Mô hình Sản phẩm",
 *     @OA\Property(property="id", type="integer", example=1, description="ID duy nhất của sản phẩm"),
 *     @OA\Property(property="category_id", type="integer", example=1, description="ID của danh mục thuộc về (ví dụ: Apple)"),
 *     @OA\Property(property="name", type="string", example="iPhone 15 Pro Max", description="Tên sản phẩm"),
 *     @OA\Property(property="slug", type="string", example="iphone-15-pro-max-123456", description="Đường dẫn thân thiện (unique)"),
 *     @OA\Property(property="sku", type="string", example="APP-SKU-001", description="Mã kho hàng (Stock Keeping Unit)"),
 *     @OA\Property(property="description", type="string", description="Mô tả chi tiết sản phẩm"),
 *     @OA\Property(property="screen_size", type="string", example="6.7 inches", description="Kích thước màn hình"),
 *     @OA\Property(property="screen_tech", type="string", example="Super Retina XDR OLED", description="Công nghệ màn hình"),
 *     @OA\Property(property="rear_camera", type="string", example="48MP + 12MP + 12MP", description="Thông số camera sau"),
 *     @OA\Property(property="front_camera", type="string", example="12MP", description="Thông số camera trước"),
 *     @OA\Property(property="chipset", type="string", example="Apple A17 Pro", description="Bộ xử lý CPU"),
 *     @OA\Property(property="battery", type="string", example="4441 mAh", description="Dung lượng pin"),
 *     @OA\Property(property="charging_speed", type="string", example="25W", description="Tốc độ sạc nhanh"),
 *     @OA\Property(property="operating_system", type="string", example="iOS 17", description="Hệ điều hành"),
 *     @OA\Property(property="weight_g", type="integer", example=221, description="Trọng lượng (gram)"),
 *     @OA\Property(property="brand", type="string", example="Apple", description="Thương hiệu"),
 *     @OA\Property(property="base_price", type="number", example=34990000, description="Giá gốc niêm yết"),
 *     @OA\Property(property="sale_price", type="number", nullable=true, example=32990000, description="Giá khuyến mãi (nếu có)"),
 *     @OA\Property(property="image_url", type="string", description="Đường dẫn ảnh chính"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Trạng thái kinh doanh (hiện/ẩn)"),
 *     @OA\Property(property="is_featured", type="boolean", example=false, description="Sản phẩm nổi bật/xu hướng"),
 *     @OA\Property(property="category", ref="#/components/schemas/Category"),
 *     @OA\Property(property="variants", type="array", @OA\Items(ref="#/components/schemas/ProductVariant"))
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Lấy danh sách sản phẩm",
     *     description="API trả về danh sách sản phẩm có hỗ trợ tìm kiếm, lọc, sắp xếp và cursor-based pagination (infinite scrolling). 
     *     Mặc định chỉ trả về sản phẩm đang hoạt động (is_active = true). 
     *     Nếu truyền all=true thì trả về tất cả sản phẩm (dành cho admin).",
     *     operationId="getProducts",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="ID danh mục sản phẩm. Hệ thống sẽ tự động lấy cả danh mục con.",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Tìm kiếm theo tên sản phẩm hoặc SKU",
     *         required=false,
     *         @OA\Schema(type="string", example="iphone")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Tìm kiếm theo tên sản phẩm hoặc SKU",
     *         required=false,
     *         @OA\Schema(type="string", example="iphone")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Lọc sản phẩm có giá >= giá trị này",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=1000000)
     *     ),
     *
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Lọc sản phẩm có giá <= giá trị này",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=5000000)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Trường dùng để sắp xếp",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"base_price","name","created_at"},
     *             default="created_at"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Thứ tự sắp xếp",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"asc","desc"},
     *             default="desc"
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         description="Cursor dùng cho cursor pagination để lấy trang tiếp theo",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="all",
     *         in="query",
     *         description="Nếu true thì trả về tất cả sản phẩm (bao gồm cả sản phẩm ẩn). Dành cho admin.",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách sản phẩm",
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Danh sách sản phẩm",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             ),
     *
     *             @OA\Property(
     *                 property="path",
     *                 type="string",
     *                 example="http://localhost/api/products"
     *             ),
     *
     *             @OA\Property(
     *                 property="per_page",
     *                 type="integer",
     *                 example=12,
     *                 description="Số lượng sản phẩm mỗi trang"
     *             ),
     *
     *             @OA\Property(
     *                 property="next_cursor",
     *                 type="string",
     *                 nullable=true,
     *                 example="eyJpZCI6MTJ9",
     *                 description="Cursor cho trang tiếp theo"
     *             ),
     *
     *             @OA\Property(
     *                 property="next_page_url",
     *                 type="string",
     *                 nullable=true,
     *                 example="http://localhost/api/products?cursor=eyJpZCI6MTJ9",
     *                 description="URL trang tiếp theo"
     *             ),
     *
     *             @OA\Property(
     *                 property="prev_cursor",
     *                 type="string",
     *                 nullable=true,
     *                 example=null,
     *                 description="Cursor trang trước"
     *             ),
     *
     *             @OA\Property(
     *                 property="prev_page_url",
     *                 type="string",
     *                 nullable=true,
     *                 example=null,
     *                 description="URL trang trước"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Tham số không hợp lệ"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::select([
            'id', 'category_id', 'brand_id', 'name', 'slug', 'sku', 'base_price', 'stock_quantity',
            'sale_price', 'image_url', 'is_active', 'is_featured', 'created_at'
        ])->with(['category:id,name,slug', 'brand:id,name,logo,slug']);

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
            $products = $query->orderBy('id')->paginate(10);
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
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Chi tiết sản phẩm",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại")
     * )
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
        ])->find($id);
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
     * @OA\Post(
     *     path="/products/create",
     *     summary="Tạo sản phẩm mới (Yêu cầu: superadmin, admin)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "base_price", "category_id"},
     *                 @OA\Property(property="name", type="string", example="iPhone 15 Pro Max 256GB"),
     *                 @OA\Property(property="description", type="string", example="Mô tả chi tiết sản phẩm"),
     *                 @OA\Property(property="base_price", type="number", example=5000000),
     *                 @OA\Property(property="sale_price", type="number", nullable=true),
     *                 @OA\Property(property="sku", type="string", example="SKU12345"),
     *                 @OA\Property(property="category_id", type="integer", example=3)  ,
     *                 @OA\Property(property="stock_quantity", type="integer", example=10),
     *                 @OA\Property(property="image", type="string", format="binary", description="Ảnh đại diện (Sẽ được nén webp)"),
     *                 @OA\Property(property="gallery_images[]", type="array", @OA\Items(type="string", format="binary"), description="Danh sách ảnh phụ (Tối đa 5)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được tạo thành công!"),
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
     *     @OA\Response(response=422, description="Lỗi validation")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['slug'] = Str::slug($request->name) . '-' . time();

        if ($request->hasFile('image')) {
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'products',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validatedData['image_url'] = $uploaded['secure_url'];
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

        // Tự động tạo variant default
        $product->variants()->create([
            'sku'            => $productData['sku'] . '-DEFAULT',
            'price'          => $productData['base_price'],
            'stock_quantity' => $productData['stock_quantity'] ?? 0,
            'image_url'      => $productData['image_url'] ?? null,
            'is_available'   => true,
        ]);

        // Upload gallery images
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $index => $galleryImage) {
                $uploaded = cloudinary()->uploadApi()->upload($galleryImage->getRealPath(), [
                    'folder' => 'products_gallery',
                    'transformation' => [
                        'width' => 1000,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'fetch_format' => 'webp'
                    ]
                ]);
                
                $product->images()->create([
                    'image_url' => $uploaded['secure_url'],
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
     * @OA\Post(
     *     path="/products/{id}",
     *     summary="Cập nhật sản phẩm (Yêu cầu: superadmin, admin)",
     *     description="Vì Laravel không hỗ trợ file upload qua PUT trực tiếp một cách tốt nhất, hãy dùng POST kèm tham số _method=PUT",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="base_price", type="number"),
     *                 @OA\Property(property="sale_price", type="number", nullable=true),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="stock_quantity", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Ảnh đại diện mới"),
     *                 @OA\Property(property="gallery_images[]", type="array", @OA\Items(type="string", format="binary"), description="Danh sách ảnh phụ mới (Sẽ ghi đè ảnh cũ)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được cập nhật thành công!"),
     *             @OA\Property(property="product", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập")
     * )
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
                if (Str::contains($oldUrl, 'res.cloudinary.com')) {
                    $parts = explode('/upload/', $oldUrl);
                    if (isset($parts[1])) {
                        $path = preg_replace('/^v\d+\//', '', $parts[1]); 
                        $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
                        if ($publicId && $publicId !== '.') {
                            cloudinary()->uploadApi()->destroy($publicId);
                        }
                    }
                } else {
                    Storage::disk('public')->delete($oldUrl);
                }
            }
            
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'products',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validatedData['image_url'] = $uploaded['secure_url'];
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
        if ($request->hasFile('gallery_images')) {
            // Replace all existing gallery images
            foreach ($product->images as $oldImage) {
                if ($oldUrl = $oldImage->getRawOriginal('image_url')) {
                    if (Str::contains($oldUrl, 'res.cloudinary.com')) {
                        $parts = explode('/upload/', $oldUrl);
                        if (isset($parts[1])) {
                            $path = preg_replace('/^v\d+\//', '', $parts[1]); 
                            $publicId = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
                            if ($publicId && $publicId !== '.') cloudinary()->uploadApi()->destroy($publicId);
                        }
                    } else {
                        Storage::disk('public')->delete($oldUrl);
                    }
                }
                $oldImage->delete(); // Or permanent delete since the record logic uses soft delete maybe
            }

            foreach ($request->file('gallery_images') as $index => $galleryImage) {
                $uploaded = cloudinary()->uploadApi()->upload($galleryImage->getRealPath(), [
                    'folder' => 'products_gallery',
                    'transformation' => [
                        'width' => 1000,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'fetch_format' => 'webp'
                    ]
                ]);
                
                $product->images()->create([
                    'image_url' => $uploaded['secure_url'],
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
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Xóa sản phẩm (Yêu cầu: superadmin, admin)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được xóa thành công!")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Không có quyền truy cập"),
     *     @OA\Response(response=404, description="Sản phẩm không tồn tại")
     * )
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
