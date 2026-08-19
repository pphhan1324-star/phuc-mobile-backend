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
     * Lấy danh sách Sản phẩm Yêu thích của User (Có phân trang, tìm kiếm).
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
     * Thêm sản phẩm vào danh sách Yêu thích.
     * Dùng hàm firstOrCreate để chống lỗi: Dù bấm thêm nhiều lần thì chỉ lưu 1 dòng duy nhất.
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
     * Bỏ Yêu thích 1 sản phẩm.
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
     * Xóa sạch toàn bộ danh sách Yêu thích của User hiện tại.
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
