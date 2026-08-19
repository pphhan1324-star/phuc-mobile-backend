<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class ProductVariantController extends Controller
{
    public function index($productId)
    {
        if (!Product::where('id', $productId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $variants = ProductVariant::where('product_id', $productId)->get();
        return response()->json(['success' => true, 'data' => $variants]);
    }

    public function store(StoreProductVariantRequest $request, $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $validated = $request->validated();
        $validated['product_id'] = $productId;
        if (empty($validated['price'])) {
            $validated['price'] = $product->sale_price ?? $product->base_price ?? 0;
        }

        if (!empty($validated['color'])) {
            $color = \App\Models\Color::firstOrCreate(['name' => $validated['color']], ['hex_code' => '#000000']);
            $validated['color_id'] = $color->id;
        }
        if (!empty($validated['size']) || !empty($validated['storage'])) {
            $sVal = $validated['storage'] ?? $validated['size'];
            $storage = \App\Models\StorageOption::firstOrCreate(['value' => $sVal]);
            $validated['storage_id'] = $storage->id;
        }
        if (!empty($validated['ram'])) {
            $ram = \App\Models\Ram::firstOrCreate(['value' => $validated['ram']]);
            $validated['ram_id'] = $ram->id;
        }

        if (empty($validated['sku'])) {
            $cSlug = Str::slug($validated['color'] ?? 'def');
            $sSlug = Str::slug($validated['storage'] ?? $validated['size'] ?? 'def');
            $validated['sku'] = $product->sku . '-' . $cSlug . '-' . $sSlug . '-' . rand(100, 999);
        }

        if ($request->hasFile('image')) {
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'variants',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validated['image_url'] = $uploaded['secure_url'];
        }

        $variant = ProductVariant::create($validated);
        $variant->load(['color', 'storage', 'ram']);

        // Tự động tính lại và đồng bộ tổng số lượng tồn kho sản phẩm cha
        $totalStock = ProductVariant::where('product_id', $productId)->sum('stock_quantity');
        Product::where('id', $productId)->update(['stock_quantity' => $totalStock]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm biến thể mới thành công!',
            'data' => $variant
        ], 201);
    }

    public function update(UpdateProductVariantRequest $request, $productId, $variantId)
    {
        $variant = ProductVariant::where('product_id', $productId)->find($variantId);
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Biến thể không tồn tại'], 404);
        }

        $validated = $request->validated();

        if (!empty($validated['color'])) {
            $color = \App\Models\Color::firstOrCreate(['name' => $validated['color']], ['hex_code' => '#000000']);
            $validated['color_id'] = $color->id;
        }
        if (!empty($validated['size']) || !empty($validated['storage'])) {
            $sVal = $validated['storage'] ?? $validated['size'];
            $storage = \App\Models\StorageOption::firstOrCreate(['value' => $sVal]);
            $validated['storage_id'] = $storage->id;
        }
        if (!empty($validated['ram'])) {
            $ram = \App\Models\Ram::firstOrCreate(['value' => $validated['ram']]);
            $validated['ram_id'] = $ram->id;
        }

        if ($request->hasFile('image')) {
            if ($oldUrl = $variant->getRawOriginal('image_url')) {
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
            $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'variants',
                'transformation' => [
                    'width' => 1000,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'fetch_format' => 'webp'
                ]
            ]);
            $validated['image_url'] = $uploaded['secure_url'];
        }

        $variant->update($validated);
        $variant->load(['color', 'storage', 'ram']);

        // Tự động tính lại và đồng bộ tổng số lượng tồn kho sản phẩm cha
        $totalStock = ProductVariant::where('product_id', $productId)->sum('stock_quantity');
        Product::where('id', $productId)->update(['stock_quantity' => $totalStock]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật biến thể thành công!',
            'data' => $variant->fresh()
        ], 200);
    }

    public function destroy($productId, $variantId)
    {
        $variant = ProductVariant::where('product_id', $productId)->find($variantId);
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Biến thể không tồn tại'], 404);
        }

        // Xóa ảnh của biến thể nếu có
        if ($oldUrl = $variant->getRawOriginal('image_url')) {
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

        $variant->delete();

        // Tự động tính lại và đồng bộ tổng số lượng tồn kho sản phẩm cha
        $totalStock = ProductVariant::where('product_id', $productId)->sum('stock_quantity');
        Product::where('id', $productId)->update(['stock_quantity' => $totalStock]);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa biến thể thành công!'
        ]);
    }
}
