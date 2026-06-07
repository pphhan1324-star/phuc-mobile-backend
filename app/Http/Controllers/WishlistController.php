<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\WishListRequest;
use App\Models\Product;

class WishlistController extends Controller
{
    /**
     * @OA\Get(
     *     path="/wishlist",
     *     summary="Lấy danh sách sản phẩm yêu thích",
     *     description="Trả về danh sách wishlist của user kèm phân trang. Hỗ trợ tìm kiếm theo tên và lọc theo danh mục.",
     *     tags={"Wishlist Manager"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, description="Tìm theo tên sản phẩm", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", required=false, description="Lọc theo ID danh mục", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="order", in="query", required=false, description="Thứ tự sắp xếp theo ngày thêm (asc/desc)", @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Số bản ghi mỗi trang", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy danh sách yêu thích thành công!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Đối tượng phân trang"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tồn sản phẩm này trong danh sách yêu thích",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Sản phẩm không có trong danh sách yêu thích!")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - Chưa đăng nhập")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->query('per_page', 10);
        $order = $request->query('order', 'desc');

        $query = Wishlist::where('user_id', $user->id);

        // Tìm kiếm theo tên sản phẩm hoặc lọc theo danh mục
        if ($request->filled('search') || $request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                if ($request->filled('search')) {
                    $q->where('name', 'LIKE', "%{$request->search}%");
                }
                if ($request->filled('category_id')) {
                    $q->where('category_id', $request->category_id);
                }
            });
        }

        // Mặc định sắp xếp theo ngày thêm vào wishlist
        $query->orderBy('created_at', $order);

        $wishlists = $query->with('product:id,name,slug,base_price,sale_price,image_url,category_id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách yêu thích thành công!',
            'data' => $wishlists
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/wishlist/add",
     *     summary="Thêm sản phẩm vào danh sách yêu thích (Yêu cầu đăng nhập)",
     *     tags={"Wishlist Manager"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id"},
     *             @OA\Property(
     *                 property="product_id",
     *                 type="integer",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Thêm vào wishlist thành công (Chống trùng lặp & Chỉ sản phẩm hoạt động)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sản phẩm đã được thêm vào danh sách yêu thích thành công!"),
     *             @OA\Property(
     *                 property="wishlist",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="product_id", type="integer", example=5)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Chưa đăng nhập"
     *     )
     * )
     */
    public function store(WishlistRequest $request)
    {
        $validatedData = $request->validated();
        $user = Auth::user();

        // Chống trùng lặp: Chỉ tạo nếu chưa tồn tại
        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $validatedData['product_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sản phẩm đã được thêm vào danh sách yêu thích thành công!',
            'wishlist' => $wishlist
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/wishlist/remove/{product_id}",
     *     summary="Xóa sản phẩm khỏi danh sách yêu thích (Yêu cầu đăng nhập)",
     *     tags={"Wishlist Manager"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         required=true,
     *         description="ID sản phẩm cần xóa khỏi wishlist",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã xóa sản phẩm khỏi danh sách")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Chưa đăng nhập"
     *     )
     * )
     */
    public function remove($id)
    {
        $user = Auth::user();

        $itemDelete = Wishlist::where('user_id', $user->id)
            ->where('product_id', $id)
            ->first();

        if (!$itemDelete) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy sản phẩm trong danh sách yêu thích"
            ], 404);
        }

        $itemDelete->delete();



        return response()->json([
            'success' => true,
            'message' => "Đã xóa sản phẩm khỏi danh sách",
        ]);

    }


    /**
     * @OA\Delete(
     *     path="/wishlist/clear",
     *     summary="Xóa toàn bộ danh sách yêu thích (Yêu cầu đăng nhập)",
     *     tags={"Wishlist Manager"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Làm trống wishlist thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã xóa toàn bộ danh sách")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function clear()
    {
        $user = Auth::user();

        Wishlist::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa toàn bộ danh sách"
        ], 200);
    }


}
