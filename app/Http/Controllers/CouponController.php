<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Exception;

/**
 * @OA\Schema(
 *     schema="Coupon",
 *     title="Coupon",
 *     required={"code", "type", "value", "start_date", "end_date"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="SUMMER2024"),
 *     @OA\Property(property="type", type="string", enum={"percent", "fixed"}, example="percent"),
 *     @OA\Property(property="value", type="number", format="float", example=10.5),
 *     @OA\Property(property="min_order_amount", type="number", format="float", nullable=true, example=100000),
 *     @OA\Property(property="max_discount", type="number", format="float", nullable=true, example=50000),
 *     @OA\Property(property="usage_limit", type="integer", nullable=true, example=100),
 *     @OA\Property(property="used_count", type="integer", example=0),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
 *     @OA\Property(property="is_active", type="boolean", example=true)
 * )
 */
class CouponController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * @OA\Get(
     *     path="/admin/coupons",
     *     summary="Danh sách mã giảm giá (Admin)",
     *     tags={"Admin Coupons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Coupon")))
     * )
     */
    public function index()
    {
        $coupons = Coupon::all();
        return response()->json($coupons);
    }

    /**
     * @OA\Post(
     *     path="/admin/coupons",
     *     summary="Tạo mã giảm giá mới (Admin)",
     *     tags={"Admin Coupons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Coupon")
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công", @OA\JsonContent(ref="#/components/schemas/Coupon")),
     *     @OA\Response(response=422, description="Dữ liệu không hợp lệ")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code|max:50',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $coupon = Coupon::create($validated);
        return response()->json($coupon, 201);
    }

    /**
     * @OA\Get(
     *     path="/admin/coupons/{id}",
     *     summary="Xem chi tiết mã giảm giá (Admin)",
     *     tags={"Admin Coupons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công", @OA\JsonContent(ref="#/components/schemas/Coupon")),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function show(Coupon $coupon)
    {
        return response()->json($coupon);
    }

    /**
     * @OA\Put(
     *     path="/admin/coupons/{id}",
     *     summary="Cập nhật mã giảm giá (Admin)",
     *     tags={"Admin Coupons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Coupon")
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công", @OA\JsonContent(ref="#/components/schemas/Coupon")),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|unique:coupons,code,' . $coupon->id . '|max:50',
            'type' => 'sometimes|required|in:percent,fixed',
            'value' => 'sometimes|required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $coupon->update($validated);
        return response()->json($coupon);
    }

    /**
     * @OA\Delete(
     *     path="/admin/coupons/{id}",
     *     summary="Xóa mã giảm giá (Admin)",
     *     tags={"Admin Coupons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Xóa thành công"),
     *     @OA\Response(response=404, description="Không tìm thấy")
     * )
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/coupons/apply",
     *     summary="Áp dụng mã giảm giá cho giỏ hàng (User)",
     *     tags={"Coupons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "order_amount"},
     *             @OA\Property(property="code", type="string", example="SUMMER2024"),
     *             @OA\Property(property="order_amount", type="number", example=200000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="coupon_code", type="string", example="SUMMER2024"),
     *             @OA\Property(property="discount_amount", type="number", example=50000),
     *             @OA\Property(property="final_amount", type="number", example=150000),
     *             @OA\Property(property="message", type="string", example="Áp dụng mã giảm giá thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Mã không hợp lệ hoặc không đủ điều kiện",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Mã giảm giá đã hết hạn.")
     *         )
     *     )
     * )
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        try {
            $coupon = $this->discountService->validateCoupon($request->code, $request->order_amount);
            $discountAmount = $this->discountService->calculateDiscount($coupon, $request->order_amount);

            return response()->json([
                'success' => true,
                'coupon_code' => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_amount' => max(0, $request->order_amount - $discountAmount),
                'message' => 'Áp dụng mã giảm giá thành công.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
