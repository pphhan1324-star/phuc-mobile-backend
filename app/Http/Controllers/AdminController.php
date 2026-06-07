<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Admin;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * @OA\Post(
     *     path="/admin/login",
     *     operationId="adminLogin",
     *     tags={"Admin"},
     *     summary="Đăng nhập Admin",
     *     description="API cho phép admin đăng nhập vào hệ thống bằng email và mật khẩu. Nếu thông tin hợp lệ, hệ thống sẽ trả về JWT token để sử dụng cho các API yêu cầu xác thực.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Thông tin đăng nhập của admin",
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="admin@gmail.com",
     *                 description="Email đăng nhập của admin"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password",
     *                 description="Mật khẩu đăng nhập"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 example="eyJ0eXAiOiJKV1QiLCJh..."
     *             ),
     *             @OA\Property(
     *                 property="admin",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="email", type="string", example="admin@gmail.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-01T10:00:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Sai email hoặc mật khẩu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Email hoặc mật khẩu admin không đúng"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu gửi lên không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "email": {"The email field is required."},
     *                     "password": {"The password field is required."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('admin-api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu admin không đúng'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'admin' => auth('admin-api')->user()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/logout",
     *     summary="Đăng xuất Admin",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Đăng xuất thành công"),
     *     @OA\Response(response=401, description="Chưa xác thực")
     * )
     */
    public function logout()
    {
        auth('admin-api')->logout();
        return response()->json(['success' => true, 'message' => 'Admin logout thành công']);
    }

    /**
     * @OA\Get(
     *     path="/admin/me",
     *     summary="Lấy thông tin Admin đang đăng nhập",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200, 
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="admin", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="email", type="string", example="admin@gmail.com"),
     *                 @OA\Property(property="role", type="string", example="superadmin"),
     *                 @OA\Property(property="is_active", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Chưa xác thực")
     * )
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'admin' => auth('admin-api')->user()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/staff",
     *     summary="Lấy danh sách admin/nhân viên",
     *     tags={"Staff Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200, 
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data", 
     *                 type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="role", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Chưa xác thực"),
     *     @OA\Response(response=403, description="Không có quyền")
     * )
     */
    public function index()
    {
        $admins = Admin::all();
        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/staff",
     *     summary="Tạo admin/nhân viên mới",
     *     tags={"Staff Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","role"},
     *             @OA\Property(property="name", type="string", example="New Staff"),
     *             @OA\Property(property="email", type="string", example="staff@gmail.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "staff"}, example="staff"),
     *             @OA\Property(property="is_active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tạo tài khoản quản trị thành công"),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(StoreAdminRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $admin = Admin::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tài khoản quản trị thành công',
            'data' => $admin
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/admin/staff/{id}",
     *     summary="Xem chi tiết admin/nhân viên",
     *     tags={"Staff Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function show(string $id)
    {
        $admin = Admin::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $admin
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/staff/{id}",
     *     summary="Cập nhật admin/nhân viên",
     *     tags={"Staff Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật tài khoản thành công"),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function update(UpdateAdminRequest $request, string $id)
    {
        $admin = Admin::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $admin->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tài khoản thành công',
            'data' => $admin
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/admin/staff/{id}",
     *     summary="Xóa admin/nhân viên",
     *     tags={"Staff Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200, 
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Xóa tài khoản thành công")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function destroy(string $id)
    {
        $admin = Admin::findOrFail($id);

        // Không cho phép superadmin tự xóa chính mình nếu cần (tùy logic)
        if ($admin->role === 'superadmin' && auth('admin-api')->id() === $admin->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản superadmin của chính mình'
            ], 403);
        }

        // Đổi email để giải phóng email gốc khi xóa (Soft Delete)
        $admin->email = $admin->email . '_deleted_' . now()->timestamp;
        $admin->save();

        $admin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa tài khoản thành công'
        ]);
    }
}

