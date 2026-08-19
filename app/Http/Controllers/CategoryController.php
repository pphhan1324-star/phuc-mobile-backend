<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Lấy danh sách Danh mục.
     * Hỗ trợ lấy theo dạng Cây (Tree - Cha con) hoặc phẳng (Flat - cho dropdown).
     */
    public function index(Request $request)
    {
        $showAll = $request->boolean('all');

        if ($request->boolean('tree')) {
            // Lấy danh mục gốc kèm theo các con của chúng
            $query = Category::with([
                'children' => function ($q) use ($showAll) {
                    if (!$showAll) {
                        $q->where('is_active', 1)->orderBy('sort_order');
                    } else {
                        $q->orderBy('sort_order');
                    }
                }
            ])
                ->whereNull('parent_id');

            if (!$showAll) {
                $query->where('is_active', 1);
            }

            $categories = $query->orderBy('sort_order')->get();
        } else {
            // Lấy danh sách phẳng (cho dropdown chọn cha)
            $query = Category::with('parent');

            if (!$showAll) {
                $query->where('is_active', 1);
            }

            $categories = $query->orderBy('sort_order')->get();
        }

        return response()->json($categories);
    }

    /**
     * Thêm danh mục mới (Chỉ dành cho Admin).
     * Xử lý lỗi parent_id rỗng và tự động tạo Slug (đường dẫn chuẩn SEO).
     */
    public function store(StoreCategoryRequest $request)
    {
        if ($request->has('parent_id')) {
            $val = $request->parent_id;
            if ($val === '' || $val === 'null' || $val === 'undefined' || $val === '0' || $val === 0) {
                $request->merge(['parent_id' => null]);
            }
        }

        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo danh mục thành công',
            'data' => $category
        ], 201);
    }

    /**
     * Lấy chi tiết 1 Danh mục kèm theo các danh mục con và đếm số lượng sản phẩm.
     */
    public function show($id)
    {
        $category = Category::with([
            'parent:id,name,slug',
            'children' => function ($q) {
                $q->withCount('products')->orderBy('sort_order');
            }
        ])->findOrFail($id);

        return response()->json($category);
    }

    /**
     * Cập nhật thông tin Danh mục (Chỉ Admin).
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        if ($request->has('parent_id')) {
            $val = $request->parent_id;
            if ($val === '' || $val === 'null' || $val === 'undefined' || $val === '0' || $val === 0) {
                $request->merge(['parent_id' => null]);
            }
        }

        $category = Category::findOrFail($id);

        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']) . '-' . substr($id, -4);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công',
            'data' => $category
        ]);
    }

    /**
     * Xóa Danh mục (Chỉ Admin).
     * Ràng buộc an toàn: Không cho phép xóa nếu danh mục đang chứa sản phẩm.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Kiểm tra xem có sản phẩm nào đang thuộc danh mục này không
        $productCount = $category->products()->count();
        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Không thể xóa danh mục này vì đang có $productCount sản phẩm thuộc về nó."
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }
}
