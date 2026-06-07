<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description'
    ];

    /**
     * Lấy các sản phẩm thuộc thương hiệu này
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
