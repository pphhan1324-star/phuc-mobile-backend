<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="UserAddress",
 *     title="UserAddress",
 *     description="Mô hình địa chỉ người dùng",
 *     @OA\Property(property="id", type="integer", example=1, description="ID địa chỉ"),
 *     @OA\Property(property="user_id", type="integer", example=1, description="ID người dùng"),
 *     @OA\Property(property="receiver_name", type="string", example="Nguyễn Văn A", description="Tên người nhận"),
 *     @OA\Property(property="receiver_phone", type="string", example="0987654321", description="Số điện thoại người nhận"),
 *     @OA\Property(property="province_id", type="integer", nullable=true, example=202, description="ID Tỉnh/Thành phố"),
 *     @OA\Property(property="district_id", type="integer", nullable=true, example=111, description="ID Quận/Huyện"),
 *     @OA\Property(property="ward_id", type="integer", nullable=true, example=333, description="ID Phường/Xã"),
 *     @OA\Property(property="address_detail", type="string", example="Số 123, Đường ABC", description="Địa chỉ cụ thể"),
 *     @OA\Property(property="is_default", type="boolean", example=true, description="Có phải địa chỉ mặc định không"),
 *     @OA\Property(property="type", type="string", example="home", enum={"home", "office", "other"}, description="Loại địa chỉ"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_name',
        'receiver_phone',
        'province_id',
        'district_id',
        'ward_id',
        'address_detail',
        'is_default',
        'type'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Lấy người dùng sở hữu địa chỉ này
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lấy Tỉnh/Thành phố của địa chỉ
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Lấy Quận/Huyện của địa chỉ
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Lấy Phường/Xã của địa chỉ
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }
}
