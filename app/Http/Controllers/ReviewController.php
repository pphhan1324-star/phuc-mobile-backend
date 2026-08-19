<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // Lấy danh sách đánh giá của một sản phẩm
    public function index($productId)
    {
        $reviews = Review::with('user:id,name')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $reviews->avg('rating') ?? 0;
        $reviewCount = $reviews->count();

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'average_rating' => round($averageRating, 1),
                'review_count' => $reviewCount
            ]
        ]);
    }

    // Gửi đánh giá mới
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        // Kiểm tra xem sản phẩm có tồn tại không
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        // Bổ sung: Kiểm tra xem user đã mua sản phẩm này chưa (Đơn hàng phải ở trạng thái "Hoàn thành")
        $hasPurchased = \App\Models\Order::where('user_id', auth()->id())
            ->where('order_status', 'delivered')
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })->exists();

        if (!$hasPurchased) {
            return response()->json([
                'success' => false, 
                'message' => 'Bạn chỉ có thể đánh giá sau khi đã mua và nhận hàng thành công!'
            ], 403);
        }

        // Tạo hoặc Cập nhật đánh giá (Mỗi người 1 sản phẩm 1 comment)
        $review = Review::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $productId,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
                'status' => 'approved' // Ở hệ thống thật có thể để 'pending' chờ duyệt
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Cảm ơn bạn đã đánh giá sản phẩm!',
            'data' => $review->load('user:id,name')
        ], 201);
    }

    // Lấy toàn bộ đánh giá (Cho Admin)
    public function adminIndex()
    {
        $reviews = Review::with(['user:id,name,email', 'product:id,name,image_url'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    // Xóa đánh giá (Admin)
    public function destroy($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đánh giá'], 404);
        }
        $review->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa bình luận']);
    }
}
