<?php

namespace App\Services;

use App\Models\Coupon;
use Exception;

class DiscountService
{
    /**
     * Xác thực mã giảm giá.
     *
     * @param string $code
     * @param float $orderAmount
     * @return Coupon
     * @throws Exception
     */
    public function validateCoupon(string $code, float $orderAmount): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            throw new Exception('Mã giảm giá không tồn tại.');
        }

        if (!$coupon->is_active) {
            throw new Exception('Mã giảm giá này đã bị vô hiệu hóa.');
        }

        $now = now()->toDateString();
        $startDate = $coupon->start_date->toDateString();
        $endDate = $coupon->end_date->toDateString();

        if ($startDate > $now) {
            throw new Exception('Mã giảm giá chưa đến ngày bắt đầu sử dụng.');
        }

        if ($endDate < $now) {
            throw new Exception('Mã giảm giá này đã hết hạn.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new Exception('Mã giảm giá đã hết lượt sử dụng.');
        }

        if ($orderAmount < ($coupon->min_order_amount ?? 0)) {
            throw new Exception('Đơn hàng không đủ giá trị tối thiểu để áp dụng mã này (Tối thiểu: ' . number_format($coupon->min_order_amount) . 'đ).');
        }

        return $coupon;
    }

    /**
     * Tính toán số tiền được giảm.
     *
     * @param Coupon $coupon
     * @param float $orderAmount
     * @return float
     */
    public function calculateDiscount(Coupon $coupon, float $orderAmount): float
    {
        return $coupon->getDiscountAmount($orderAmount);
    }

    /**
     * Áp dụng mã giảm giá (tăng biến đếm lượt dùng).
     *
     * @param string $code
     * @return bool
     */
    public function applyCoupon(string $code): bool
    {
        // Sử dụng lockForUpdate() để khóa dữ liệu mã giảm giá trong Transaction, chống lỗi đồng thời (Race Condition)
        $coupon = Coupon::where('code', $code)->lockForUpdate()->first();
        if ($coupon && $coupon->isValid()) {
            $coupon->increment('used_count');
            return true;
        }
        return false;
    }
}
