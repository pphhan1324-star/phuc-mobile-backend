<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="Category",
 *     title="Category",
 *     description="Mô hình Danh mục sản phẩm",
 *     @OA\Property(property="id", type="integer", example=1, description="ID duy nhất của danh mục"),
 *     @OA\Property(property="name", type="string", example="Điện thoại di động", description="Tên hiển thị của danh mục"),
 *     @OA\Property(property="slug", type="string", example="dien-thoai-di-dong-65e8a1", description="Đường dẫn thân thiện (tự động tạo từ tên + unique id)"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID của danh mục cha (null nếu là danh mục gốc)"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Mô tả danh mục", description="Mô tả chi tiết về danh mục"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Trạng thái hiển thị (true: hiện, false: ẩn)"),
 *     @OA\Property(property="sort_order", type="integer", example=0, description="Thứ tự sắp xếp (số càng nhỏ càng hiện lên đầu)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Ngày tạo"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Ngày cập nhật gần nhất")
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categories",
     *     operationId="getCategories",
     *     summary="Lấy danh sách danh mục",
     *     description="API trả về danh sách danh mục sản phẩm. 
     *     Có 2 chế độ trả dữ liệu:
     *     - tree=1: Trả về cấu trúc cây (danh mục cha và danh mục con).
     *     - tree=0: Trả về danh sách phẳng (dùng cho dropdown chọn parent).
     *     Mặc định chỉ trả về danh mục đang hoạt động (is_active = 1).",
     *     tags={"Categories"},
     *
     *     @OA\Parameter(
     *         name="tree",
     *         in="query",
     *         required=false,
     *         description="Nếu =1 thì trả về cấu trúc cây (nested categories)",
     *         @OA\Schema(type="integer", enum={0,1}, default=0, example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="all",
     *         in="query",
     *         required=false,
     *         description="Nếu =1 thì trả về cả danh mục đã bị ẩn (is_active = 0). Thường dùng cho admin.",
     *         @OA\Schema(type="integer", enum={0,1}, default=0, example=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách danh mục thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Category")
     *         )
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
     * @OA\Post(
     *     path="/categories",
     *     operationId="createCategory",
     *     summary="Tạo danh mục mới",
     *     description="API tạo danh mục mới. Chỉ người dùng có quyền **admin** hoặc **superadmin** mới được phép thực hiện. 
     *     Danh mục có thể là danh mục gốc hoặc danh mục con thông qua parent_id. 
     *     API hỗ trợ upload ảnh danh mục.",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Thông tin danh mục cần tạo",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name"},
     *
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Điện thoại",
     *                     description="Tên danh mục"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="parent_id",
     *                     type="integer",
     *                     nullable=true,
     *                     example=1,
     *                     description="ID danh mục cha. Để trống hoặc 0 nếu là danh mục gốc"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Danh mục các sản phẩm điện thoại",
     *                     description="Mô tả ngắn của danh mục"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="is_active",
     *                     type="integer",
     *                     enum={0,1},
     *                     default=1,
     *                     example=1,
     *                     description="Trạng thái danh mục (1: hiển thị, 0: ẩn)"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="sort_order",
     *                     type="integer",
     *                     default=0,
     *                     example=1,
     *                     description="Thứ tự hiển thị (số càng nhỏ càng ưu tiên)"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Tạo danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Tạo danh mục thành công"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Category"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Chưa xác thực (thiếu hoặc sai token)"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập (không phải admin/superadmin)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "name": {"The name field is required."},
     *                     "image": {"The image must be a file."}
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     operationId="getCategoryDetail",
     *     tags={"Categories"},
     *     summary="Lấy chi tiết danh mục",
     *     description="API trả về thông tin chi tiết của một danh mục theo ID, bao gồm danh mục cha và danh mục con. Mỗi danh mục con sẽ có thêm số lượng sản phẩm (products_count).",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục cần lấy",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lấy dữ liệu thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             ref="#/components/schemas/Category"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/categories/{id}",
     *     operationId="updateCategory",
     *     summary="Cập nhật danh mục",
     *     description="API cập nhật thông tin danh mục. Vì Laravel không hỗ trợ multipart/form-data tốt với PUT/PATCH nên sử dụng POST kèm _method=PUT để giả lập PUT request.",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục cần cập nhật",
     *         @OA\Schema(
     *             type="integer",
     *             example=12
     *         )
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dữ liệu cập nhật danh mục",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name"},
     *
     *                 @OA\Property(
     *                     property="_method",
     *                     type="string",
     *                     description="Giả lập phương thức PUT",
     *                     example="PUT"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tên danh mục",
     *                     example="Điện thoại"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="parent_id",
     *                     type="integer",
     *                     nullable=true,
     *                     description="ID danh mục cha",
     *                     example=1
     *                 ),
     *
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Mô tả danh mục",
     *                     example="Danh mục chứa các loại điện thoại"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="is_active",
     *                     type="integer",
     *                     enum={0,1},
     *                     description="Trạng thái hoạt động",
     *                     example=1
     *                 ),
     *
     *                 @OA\Property(
     *                     property="sort_order",
     *                     type="integer",
     *                     description="Thứ tự hiển thị",
     *                     example=10
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Cập nhật danh mục thành công"
     *             ),
     *
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Category"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập hoặc token không hợp lệ"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập (chỉ admin hoặc superadmin)"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu validation không hợp lệ"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     operationId="deleteCategory",
     *     summary="Xóa danh mục",
     *     description="API dùng để xóa một danh mục theo ID. Chỉ admin hoặc superadmin mới có quyền thực hiện. 
     *     Nếu danh mục vẫn còn sản phẩm liên kết thì không thể xóa.",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của danh mục cần xóa",
     *         @OA\Schema(
     *             type="integer",
     *             example=12
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Xóa danh mục thành công",
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Xóa danh mục thành công"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập hoặc token không hợp lệ"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập (chỉ admin hoặc superadmin)"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy danh mục"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Không thể xóa danh mục vì vẫn còn sản phẩm",
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Không thể xóa danh mục này vì đang có 5 sản phẩm thuộc về nó."
     *             )
     *         )
     *     )
     * )
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
