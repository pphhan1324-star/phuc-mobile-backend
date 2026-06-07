<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageOption extends Model
{
    use HasFactory;

    // Chỉ định tên bảng trong cơ sở dữ liệu để tránh xung đột với Facade Storage
    protected $table = 'storages';

    protected $fillable = [
        'value'
    ];

    /**
     * Lấy các biến thể sản phẩm mang dung lượng bộ nhớ trong này
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'storage_id');
    }
}
