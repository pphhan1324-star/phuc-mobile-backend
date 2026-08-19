<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Http\Requests\StoreUserAddressRequest;
use App\Http\Requests\UpdateUserAddressRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{

    /**
     * Lấy danh sách địa chỉ nhận hàng của User đang đăng nhập.
     */
    public function index()
    {
        $addresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->get();
        return response()->json($addresses);
    }

    /**
     * Thêm địa chỉ mới.
     * Logic: Nếu là địa chỉ đầu tiên thì tự động set làm mặc định (is_default = true).
     */
    public function store(StoreUserAddressRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['user_id'] = $user->id;

        // Nếu đây là địa chỉ đầu tiên hoặc được đánh dấu là mặc định
        if ($user->addresses()->count() === 0) {
            $data['is_default'] = true;
        } elseif ($request->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = UserAddress::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm địa chỉ thành công',
            'data' => $address
        ], 201);
    }

    /**
     * Cập nhật thông tin địa chỉ.
     * Logic: Nếu tick chọn làm mặc định, tự động gỡ cờ mặc định ở các địa chỉ khác.
     */
    public function update(UpdateUserAddressRequest $request, $id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        $data = $request->validated();

        if ($request->is_default && !$address->is_default) {
            Auth::user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công',
            'data' => $address
        ]);
    }

    /**
     * Xóa địa chỉ.
     * Logic thông minh: Nếu xóa địa chỉ mặc định, tự động lấy địa chỉ khác (nếu có) lên làm mặc định thay thế.
     */
    public function destroy($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        $wasDefault = $address->is_default;
        $address->delete();

        // Nếu xóa địa chỉ mặc định, tự động set địa chỉ khác làm mặc định nếu còn
        if ($wasDefault) {
            $nextAddress = Auth::user()->addresses()->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa địa chỉ thành công'
        ]);
    }

    public function setDefault($id)
    {
        $user = Auth::user();
        $user->addresses()->update(['is_default' => false]);

        $address = $user->addresses()->findOrFail($id);
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt làm địa chỉ mặc định'
        ]);
    }
}
