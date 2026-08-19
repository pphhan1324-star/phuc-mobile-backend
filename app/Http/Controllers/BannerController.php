<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Lấy danh sách banner đang hoạt động cho Trang chủ (Public).
     */
    public function index(Request $request)
    {
        $query = Banner::where('is_active', true);

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        $banners = $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Lấy tất cả banner cho trang Admin.
     */
    public function adminIndex()
    {
        $banners = Banner::orderBy('sort_order', 'asc')->orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * Thêm Banner mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:4096',
            'image_url' => 'nullable|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'position' => 'required|in:home_top,home_middle,sidebar',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = url('storage/' . $path);
        }

        if (empty($validated['image_url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn file hình ảnh hoặc nhập đường dẫn ảnh!'
            ], 422);
        }

        $banner = Banner::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo Banner thành công!',
            'data' => $banner
        ], 201);
    }

    /**
     * Cập nhật Banner.
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner không tồn tại'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:150',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:4096',
            'image_url' => 'nullable|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'position' => 'sometimes|required|in:home_top,home_middle,sidebar',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = url('storage/' . $path);
        }

        $banner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật Banner thành công!',
            'data' => $banner
        ]);
    }

    /**
     * Xóa Banner.
     */
    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['success' => false, 'message' => 'Banner không tồn tại'], 404);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa Banner thành công!'
        ]);
    }
}
