<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'name',
        'type'
    ];

    /**
     * Lấy quận huyện trực thuộc của phường xã này
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
