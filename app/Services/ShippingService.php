<?php

namespace App\Services;

class ShippingService
{
    /**
     * Tính toán phí vận chuyển dựa trên mã tỉnh/thành phố.
     * 
     * @param int $provinceId ID của tỉnh/thành phố (Ví dụ: 202 cho TP.HCM)
     * @param float $subtotal Tổng tiền hàng (có thể dùng để tính freeship)
     * @return float Phí vận chuyển
     */
    public function calculateShippingFee(int $provinceId, float $subtotal = 0): float
    {
        // Chính sách Freeship: Đơn hàng từ 2,000,000đ trở lên được miễn phí vận chuyển
        if ($subtotal >= 2000000) {
            return 0;
        }

        // 202 thường là ID của TP. Hồ Chí Minh trong nhiều bộ data chuẩn (như GHN)
        // Nếu tỉnh là TP. Hồ Chí Minh
        if ($provinceId == 202) {
            return 20000; // Phí ship nội thành: 20,000đ
        }

        // Các tỉnh thành khác
        return 40000; // Phí ship ngoại tỉnh: 40,000đ
    }
}
