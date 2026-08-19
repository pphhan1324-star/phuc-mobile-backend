<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\OrderInvoiceMail;
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
     * Xử lý Đặt hàng (Dành cho User đã đăng nhập, lấy dữ liệu từ bảng Cart).
     * Bao gồm: Validate form, áp mã giảm giá, kiểm tra tồn kho, lưu đơn hàng (Order) và gửi Mail.
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
                    'variant_info' => ($cartItem->variant?->color?->name ?? (is_string($cartItem->variant?->color) ? $cartItem->variant->color : '')) . ' - ' . ($cartItem->variant?->storage?->value ?? (is_string($cartItem->variant?->storage) ? $cartItem->variant->storage : '')),
                    'price' => $cartItem->variant->sale_price ?? $cartItem->variant->price ?? $cartItem->product->sale_price ?? $cartItem->product->base_price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $cartItem->quantity * ($cartItem->variant->sale_price ?? $cartItem->variant->price ?? $cartItem->product->base_price),
                ]);

                // Trừ số lượng tồn kho biến thể
                $variant = $cartItem->variant;
                if ($variant) {
                    $variant->decrement('stock_quantity', $cartItem->quantity);
                }

                // Trừ số lượng và đồng bộ tồn kho sản phẩm gốc
                $product = $cartItem->product;
                if ($product) {
                    if ($product->variants()->count() > 0) {
                        $totalStock = $product->variants()->sum('stock_quantity');
                        $product->update(['stock_quantity' => max(0, $totalStock)]);
                    } else {
                        $product->decrement('stock_quantity', $cartItem->quantity);
                    }
                }
            }

            // 4. Nếu có mã giảm giá, áp dụng (tăng used_count)
            if ($couponId) {
                $this->discountService->applyCoupon($request->coupon_code);
            }

            // 5. Xóa giỏ hàng sau khi đặt thành công
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            // Send Invoice Email (Try catch to prevent mail failure from breaking checkout)
            try {
                $email = $request->customer_email ?? auth()->user()->email ?? null;
                if ($email) {
                    Mail::to($email)->send(new OrderInvoiceMail($order));
                }
            } catch (Exception $e) {
                \Log::error('Mail Invoice Error: ' . $e->getMessage());
            }

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
     * Checkout trực tiếp từ frontend (Dành cho Khách vãng lai, đọc giỏ hàng từ LocalStorage).
     * Giống checkout() nhưng không phụ thuộc vào bảng Cart trong Database.
     */
    public function checkoutDirect(Request $request)
    {
        $request->validate([
            'customer.name' => 'required|string',
            'customer.phone' => 'required|string',
            'customer.addr' => 'required|string',
            'paymentMethod' => 'required|string',
            'total' => 'required|numeric',
            'items' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            // Lấy user_id giả lập (hoặc lấy từ token nếu có)
            $user_id = auth()->id() ?? 1;

            $order = Order::create([
                'user_id' => $user_id,
                'order_code' => 'ORD-' . strtoupper(Str::random(10)),
                'receiver_name' => $request->input('customer.name'),
                'receiver_phone' => $request->input('customer.phone'),
                'province_id' => 1,
                'district_id' => 1,
                'ward_id' => 1,
                'address_detail' => $request->input('customer.addr'),
                'shipping_address' => $request->input('customer.addr'),
                'subtotal' => $request->input('total'),
                'total_amount' => $request->input('total'),
                'payment_method' => $request->input('paymentMethod'),
                'payment_status' => $request->input('paymentMethod') === 'online' ? 'paid' : 'pending',
                'order_status' => 'pending',
                'note' => $request->input('customer.email') ? 'Email: ' . $request->input('customer.email') : '',
            ]);

            foreach ($request->input('items') as $item) {
                // Extract product_id from key (e.g. "1|Titan|256" -> 1)
                $keyParts = explode('|', $item['key'] ?? '');
                $productId = is_numeric($keyParts[0]) ? (int)$keyParts[0] : (isset($item['product_id']) ? (int)$item['product_id'] : (isset($item['id']) ? (int)$item['id'] : 1));
                $qty = (int)($item['qty'] ?? 1);
                $colorName = $item['color'] ?? '';
                $storageVal = $item['storage'] ?? '';

                $product = Product::find($productId);
                if (!$product) {
                    $product = Product::first();
                    if ($product) {
                        $productId = $product->id;
                    }
                }

                $variant = null;

                if ($product) {
                    // Tìm biến thể khớp với màu và dung lượng
                    $variant = $product->variants()
                        ->when($colorName, function($q) use ($colorName) {
                            $q->whereHas('color', fn($c) => $c->where('name', $colorName));
                        })
                        ->when($storageVal, function($q) use ($storageVal) {
                            $q->whereHas('storage', fn($s) => $s->where('value', $storageVal));
                        })
                        ->first();

                    if (!$variant) {
                        $variant = $product->variants()->first();
                    }

                    if ($variant) {
                        $variant->decrement('stock_quantity', $qty);
                        $totalStock = $product->variants()->sum('stock_quantity');
                        $product->update(['stock_quantity' => max(0, $totalStock)]);
                    } else {
                        $product->decrement('stock_quantity', $qty);
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'variant_id' => $variant?->id,
                    'product_name' => $item['name'] ?? $product?->name ?? 'Sản phẩm',
                    'variant_info' => ($colorName || $storageVal) ? ($colorName . ' - ' . $storageVal) : 'Mặc định',
                    'price' => $item['price'] ?? 0,
                    'quantity' => $qty,
                    'subtotal' => ($item['price'] ?? 0) * $qty,
                ]);
            }

            DB::commit();

            // Send Invoice Email (Try catch to prevent mail failure from breaking checkout)
            try {
                $email = $request->input('customer.email') ?? auth()->user()->email ?? null;
                if ($email) {
                    Mail::to($email)->send(new OrderInvoiceMail($order));
                }
            } catch (\Throwable $e) {
                \Log::error('Mail Invoice Error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order' => $order
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $orders = Order::with(['items.product'])->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();
        $order = Order::with(['items.product', 'coupon'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

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
                if ($item->product) {
                    if ($item->product->variants()->count() > 0) {
                        $totalStock = $item->product->variants()->sum('stock_quantity');
                        $item->product->update(['stock_quantity' => max(0, $totalStock)]);
                    } else {
                        $item->product->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // Hoàn lại 1 lượt sử dụng cho Mã giảm giá (nếu đơn hàng có áp dụng)
            if ($order->coupon_id) {
                \App\Models\Coupon::where('id', $order->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
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

    public function adminIndex(Request $request)
    {
        $query = Order::with(['user', 'items.product'])->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->paginate($request->get('limit', 15));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function adminShow($id)
    {
        $order = Order::with(['items.product', 'user', 'coupon'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

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
                    if ($item->product) {
                        if ($item->product->variants()->count() > 0) {
                            $totalStock = $item->product->variants()->sum('stock_quantity');
                            $item->product->update(['stock_quantity' => max(0, $totalStock)]);
                        } else {
                            $item->product->increment('stock_quantity', $item->quantity);
                        }
                    }
                }

                // Hoàn lại 1 lượt sử dụng cho Mã giảm giá khi Admin hủy đơn
                if ($order->coupon_id) {
                    \App\Models\Coupon::where('id', $order->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
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
