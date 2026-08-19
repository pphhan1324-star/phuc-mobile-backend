<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Lấy danh sách Khách hàng (có phân trang và tìm kiếm theo Tên/Email).
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Lấy thông tin cơ bản của 1 Khách hàng.
     */
    public function show($id)
    {
        $user = User::select('id', 'name')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Thêm Khách hàng mới (Từ Admin).
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm người dùng thành công',
            'user' => $user
        ], 201);
    }

    //Thay kiem tra
    public function getUsers()
    {
        $users = User::select('id', 'name')->get();
        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function showUsers($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        return view('product.index', compact('user'));
    }

    /**
     * Cập nhật thông tin Khách hàng (Từ Admin).
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật người dùng thành công',
            'user' => $user
        ]);
    }

    /**
     * Xóa vĩnh viễn Khách hàng (Chỉ dành cho Admin).
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa người dùng thành công'
        ]);
    }
}
