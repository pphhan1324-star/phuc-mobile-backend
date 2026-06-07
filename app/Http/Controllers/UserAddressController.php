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
     * @OA\Get(
     *     path="/user/addresses",
     *     summary="Lấy danh sách địa chỉ của người dùng đang đăng nhập",
     *     tags={"User Addresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/UserAddress"))
     *     )
     * )
     */
    public function index()
    {
        $addresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->get();
        return response()->json($addresses);
    }

    /**
     * @OA\Post(
     *     path="/user/addresses",
     *     summary="Thêm địa chỉ mới",
     *     tags={"User Addresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"receiver_name", "receiver_phone", "province_id", "district_id", "ward_id", "address_detail", "type"},
     *             @OA\Property(property="receiver_name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="receiver_phone", type="string", example="0987654321"),
     *             @OA\Property(property="province_id", type="integer", example=202),
     *             @OA\Property(property="district_id", type="integer", example=111),
     *             @OA\Property(property="ward_id", type="integer", example=333),
     *             @OA\Property(property="address_detail", type="string", example="Số 123, Đường ABC"),
     *             @OA\Property(property="is_default", type="boolean", example=false),
     *             @OA\Property(property="type", type="string", enum={"home", "office", "other"}, example="home")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Thêm địa chỉ thành công. Nếu đây là địa chỉ đầu tiên, nó sẽ tự động được đặt làm mặc định. Nếu gửi is_default=true, các địa chỉ khác của user sẽ tự động bị bỏ mặc định.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Thêm địa chỉ thành công"),
     *             @OA\Property(property="data", ref="#/components/schemas/UserAddress")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Lỗi validation")
     * )
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
     * @OA\Put(
     *     path="/user/addresses/{id}",
     *     summary="Cập nhật địa chỉ (User)",
     *     description="Cập nhật thông tin địa chỉ của người dùng đang đăng nhập. Nếu đặt is_default=true, các địa chỉ khác sẽ tự động bỏ mặc định.",
     *     tags={"User Addresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của địa chỉ cần cập nhật",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="receiver_name", type="string", example="Nguyễn Văn A"),
     *             @OA\Property(property="receiver_phone", type="string", example="0987654321"),
     *             @OA\Property(property="province_id", type="integer", example=202),
     *             @OA\Property(property="district_id", type="integer", example=111),
     *             @OA\Property(property="ward_id", type="integer", example=333),
     *             @OA\Property(property="address_detail", type="string", example="Số 123, Đường ABC"),
     *             @OA\Property(property="is_default", type="boolean", example=true),
     *             @OA\Property(property="type", type="string", enum={"home", "office", "other"}, example="home")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật địa chỉ thành công"),
     *             @OA\Property(property="data", ref="#/components/schemas/UserAddress")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Chưa đăng nhập"),
     *     @OA\Response(response=404, description="Không tìm thấy địa chỉ"),
     *     @OA\Response(response=422, description="Lỗi validation dữ liệu")
     * )
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
     * @OA\Delete(
     *     path="/user/addresses/{id}",
     *     summary="Xóa địa chỉ",
     *     tags={"User Addresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Xóa thành công")
     * )
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

    /**
     * @OA\Patch(
     *     path="/user/addresses/{id}/set-default",
     *     summary="Đặt làm địa chỉ mặc định",
     *     tags={"User Addresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
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
