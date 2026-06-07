<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Services\DiscountService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class OrderController extends Controller
{
    protected $discountService;
    protected $shippingService;

    public function __construct(DiscountService $discountService, ShippingService $shippingService)
    {
        $this->discountService = $discountService;
        $this->shippingService = $shippingService;
    }

    /**
     * @OA\Post(
     *     path="/checkout",
     *     summary="Thanh toán đơn hàng (Checkout)",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"receiver_name", "receiver_phone", "province_id", "district_id", "ward_id", "address_detail", "payment_method"},
     *             @OA\Property(property="receiver_name", type="string", example="Nguyen Van A"),
     *             @OA\Property(property="receiver_phone", type="string", example="0123456789"),
     *             @OA\Property(property="province_id", type="integer", example=202),
     *             @OA\Property(property="district_id", type="integer", example=1442),
     *             @OA\Property(property="ward_id", type="integer", example=20109),
     *             @OA\Property(property="address_detail", type="string", example="Số 123, Đường ABC"),
     *             @OA\Property(property="payment_method", type="string", enum={"cod", "bank_transfer", "momo", "vnpay"}, example="cod"),
     *             @OA\Property(property="coupon_code", type="string", nullable=true, example="SUMMER2024"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Giao gio hanh chinh")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Đặt hàng thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đặt hàng thành công!"),
     *             @OA\Property(property="order", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Lỗi validation hoặc mã giảm giá không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Giỏ hàng của bạn đang trống.")
     *         )
     *     )
     * )
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'receiver_name' => 'required|string|max:100',
            'receiver_phone' => 'required|string|max:15',
            'province_id' => 'required|integer',
            'district_id' => 'required|integer',
            'ward_id' => 'required|integer',
            'address_detail' => 'required|string|max:255',
            'payment_method' => 'required|in:cod,bank_transfer,momo,vnpay',
            'coupon_code' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cart->items()->count() == 0) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 422);
        }

        $subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * ($item->variant->sale_price ?? $item->variant->price);
        });

        $discountAmount = 0;
        $couponId = null;

        if ($request->coupon_code) {
            try {
                $coupon = $this->discountService->validateCoupon($request->coupon_code, $subtotal);
                $discountAmount = $this->discountService->calculateDiscount($coupon, $subtotal);
                $couponId = $coupon->id;
            } catch (Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $shippingFee = $this->shippingService->calculateShippingFee($request->province_id, $subtotal);
        $totalAmount = $subtotal - $discountAmount + $shippingFee;

        try {
            DB::beginTransaction();

            // 1. Kiểm tra tồn kho và tính khả dụng (với Lock để tránh tranh chấp)
            foreach ($cart->items as $cartItem) {
                $variant = $cartItem->variant()->lockForUpdate()->first();

                if (!$variant || !$variant->is_available) {
                    throw new Exception("Sản phẩm '{$cartItem->product->name}' ({$variant->sku}) hiện không khả dụng.");
                }

                if ($variant->stock_quantity < $cartItem->quantity) {
                    throw new Exception("Sản phẩm '{$cartItem->product->name}' ({$variant->sku}) không đủ số lượng trong kho (Còn: {$variant->stock_quantity}).");
                }
            }

            // 2. Tạo đơn hàng chính
            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $couponId,
                'order_code' => 'ORD-' . strtoupper(Str::random(10)),
                'receiver_name' => $request->receiver_name,
                'receiver_phone' => $request->receiver_phone,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'ward_id' => $request->ward_id,
                'address_detail' => $request->address_detail,
                'shipping_address' => $request->address_detail, // Có thể ghép chuỗi nếu muốn
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $shippingFee,
                'total_amount' => max(0, $totalAmount),
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'note' => $request->note,
            ]);

            // 3. Xử lý từng item: Tạo OrderItem và Trừ kho
            foreach ($cart->items as $cartItem) {
                // Tạo chi tiết đơn hàng
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'variant_info' => $cartItem->variant->color . ' - ' . $cartItem->variant->size,
                    'price' => $cartItem->variant->sale_price ?? $cartItem->variant->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $cartItem->quantity * ($cartItem->variant->sale_price ?? $cartItem->variant->price),
                ]);

                // Trừ số lượng tồn kho
                $variant = $cartItem->variant;
                $variant->decrement('stock_quantity', $cartItem->quantity);
            }

            // 4. Nếu có mã giảm giá, áp dụng (tăng used_count)
            if ($couponId) {
                $this->discountService->applyCoupon($request->coupon_code);
            }

            // 5. Xóa giỏ hàng sau khi đặt thành công
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order' => $order
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/orders",
     *     summary="Danh sách đơn hàng của người dùng hiện tại",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * @OA\Get(
     *     path="/orders/{id}",
     *     summary="Chi tiết đơn hàng",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công"),
     *     @OA\Response(response=404, description="Không tìm thấy đơn hàng")
     * )
     */
    public function show($id)
    {
        $user = auth()->user();
        $order = Order::with(['items', 'coupon'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * @OA\Post(
     *     path="/orders/{id}/cancel",
     *     summary="Hủy đơn hàng",
     *     description="Chỉ được hủy khi trạng thái là pending",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Hủy thành công"),
     *     @OA\Response(response=422, description="Không thể hủy đơn hàng ở trạng thái hiện tại")
     * )
     */
    public function cancel($id)
    {
        $user = auth()->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if ($order->order_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chỉ có thể hủy đơn hàng khi trạng thái đang chờ xử lý (pending).'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $order->update(['order_status' => 'cancelled']);

            // Hoàn lại kho
            foreach ($order->items as $item) {
                if ($item->variant) {
                    $item->variant->increment('stock_quantity', $item->quantity);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đơn hàng đã được hủy thành công và hoàn lại số lượng kho.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/orders",
     *     summary="[Admin] Danh sách toàn bộ đơn hàng",
     *     tags={"Admin Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function adminIndex(Request $request)
    {
        $query = Order::with('user')->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/orders/{id}",
     *     summary="[Admin] Chi tiết đơn hàng",
     *     tags={"Admin Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thành công")
     * )
     */
    public function adminShow($id)
    {
        $order = Order::with(['items', 'user', 'coupon'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/orders/{id}/status",
     *     summary="[Admin] Cập nhật trạng thái đơn hàng",
     *     tags={"Admin Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order_status", type="string", enum={"pending", "confirmed", "processing", "shipped", "delivered", "cancelled", "returned"}),
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'nullable|in:pending,confirmed,processing,shipped,delivered,cancelled,returned',
            'payment_status' => 'nullable|in:pending,paid,failed',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->order_status;

        $updateData = [];
        if ($request->has('order_status')) $updateData['order_status'] = $request->order_status;
        if ($request->has('payment_status')) $updateData['payment_status'] = $request->payment_status;

        try {
            DB::beginTransaction();

            $order->update($updateData);

            // Nếu Admin chuyển sang trạng thái cancelled, hoàn kho nếu chưa hoàn
            if ($request->order_status === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($order->items as $item) {
                    if ($item->variant) {
                        $item->variant->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái đơn hàng thành công.',
                'order' => $order
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}
