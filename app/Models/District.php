<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id',
        'name',
        'type'
    ];

    /**
     * Lấy tỉnh thành của quận huyện này
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Lấy các phường xã trực thuộc quận huyện này
     */
    public function wards()
    {
        return $this->hasMany(Ward::class);
    }
}
