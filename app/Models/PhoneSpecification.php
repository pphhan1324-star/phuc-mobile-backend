<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneSpecification extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'screen_size',
        'screen_tech',
        'rear_camera',
        'front_camera',
        'chipset',
        'battery',
        'charging_speed',
        'operating_system',
        'weight_g',
        'material'
    ];

    /**
     * Lấy sản phẩm sở hữu thông số này
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
