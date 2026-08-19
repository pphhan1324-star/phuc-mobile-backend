<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'min_order_amount' => 'float',
        'max_discount' => 'float',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Kiểm tra xem coupon còn hiệu lực không.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();
        $startDate = $this->start_date->toDateString();
        $endDate = $this->end_date->toDateString();

        if ($startDate > $now || $endDate < $now) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Tính toán số tiền giảm giá cho một đơn hàng.
     */
    public function getDiscountAmount(float $orderAmount): float
    {
        if ($orderAmount < ($this->min_order_amount ?? 0)) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = $orderAmount * ($this->value / 100);
            if ($this->max_discount !== null && $discount > $this->max_discount) {
                return $this->max_discount;
            }
            return $discount;
        }

        // Loại 'fixed'
        return min($this->value, $orderAmount);
    }
}
