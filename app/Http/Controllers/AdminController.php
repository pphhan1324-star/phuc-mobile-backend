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
     * Xử lý Admin Đăng nhập (Kiểm tra bằng guard 'admin-api').
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = \Illuminate\Support\Facades\Auth::guard('admin-api')->attempt($credentials)) {
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

    public function logout()
    {
        \Illuminate\Support\Facades\Auth::guard('admin-api')->logout();
        return response()->json(['success' => true, 'message' => 'Admin logout thành công']);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'admin' => auth('admin-api')->user()
        ]);
    }

    public function index()
    {
        $admins = Admin::all();
        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

    /**
     * Thêm tài khoản Quản trị mới (Admin / Staff).
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

    public function show(string $id)
    {
        $admin = Admin::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $admin
        ]);
    }

    /**
     * Cập nhật thông tin Quản trị viên.
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
     * Xóa tài khoản Quản trị viên (Soft Delete).
     * Ràng buộc: Admin không được tự xóa chính mình.
     */
    public function destroy(string $id)
    {
        $admin = Admin::findOrFail($id);

        // Không cho phép admin tự xóa chính mình
        if (auth('admin-api')->id() === $admin->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản admin của chính mình'
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

