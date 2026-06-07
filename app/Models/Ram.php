<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ram extends Model
{
    use HasFactory;

    protected $fillable = [
        'value'
    ];

    /**
     * Lấy các biến thể sản phẩm mang dung lượng RAM này
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'ram_id');
    }
}
