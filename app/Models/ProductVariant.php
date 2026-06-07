<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'color_id',
        'ram_id',
        'storage_id',
        'price',
        'stock_quantity',
        'image_url',
        'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function ram()
    {
        return $this->belongsTo(Ram::class, 'ram_id');
    }

    public function storage()
    {
        return $this->belongsTo(StorageOption::class, 'storage_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
