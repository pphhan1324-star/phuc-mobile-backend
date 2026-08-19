<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Exception;

class CouponController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * Lấy danh sách toàn bộ Mã giảm giá.
     */
    public function index()
    {
        $coupons = Coupon::all();
        return response()->json($coupons);
    }

    /**
     * Tạo Mã giảm giá mới (Chỉ Admin).
     * Ràng buộc: Mã phải là duy nhất, ngày kết thúc phải sau ngày bắt đầu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code|max:50',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $coupon = Coupon::create($validated);
        return response()->json($coupon, 201);
    }

    /**
     * Lấy chi tiết 1 Mã giảm giá.
     */
    public function show(Coupon $coupon)
    {
        return response()->json($coupon);
    }

    /**
     * Cập nhật Mã giảm giá (Chỉ Admin).
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|unique:coupons,code,' . $coupon->id . '|max:50',
            'type' => 'sometimes|required|in:percent,fixed',
            'value' => 'sometimes|required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $coupon->update($validated);
        return response()->json($coupon);
    }

    /**
     * Xóa Mã giảm giá.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(null, 204);
    }

    /**
     * Người dùng Áp dụng Mã giảm giá lúc Checkout.
     * Sử dụng DiscountService để kiểm tra logic: Hết hạn chưa? Đủ điều kiện đơn hàng tối thiểu không?
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        try {
            $coupon = $this->discountService->validateCoupon($request->code, $request->order_amount);
            $discountAmount = $this->discountService->calculateDiscount($coupon, $request->order_amount);

            return response()->json([
                'success' => true,
                'coupon_code' => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_amount' => max(0, $request->order_amount - $discountAmount),
                'message' => 'Áp dụng mã giảm giá thành công.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
