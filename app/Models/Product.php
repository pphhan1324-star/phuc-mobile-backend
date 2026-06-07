<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'description',
        'base_price',
        'sale_price',
        'image_url',
        'is_featured',
        'is_active',
        'view_count',
        'stock_quantity',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class);
    }

    public function specifications()
    {
        return $this->hasOne(\App\Models\PhoneSpecification::class, 'product_id');
    }

    public function variants()
    {
        return $this->hasMany(\App\Models\ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(\App\Models\ProductImage::class)->orderBy('sort_order', 'asc');
    }

}
