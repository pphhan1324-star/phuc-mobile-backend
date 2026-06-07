<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/cart",
     *     summary="Lấy thông tin giỏ hàng",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="Session ID cho khách vãng lai",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(property="cart_id", type="integer"),
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total_quantity", type="integer"),
     *                 @OA\Property(property="total_price", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $cart = $this->getCart($request);

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                    'total_price' => 0
                ]
            ]);
        }

        $cart->load(['items.product', 'items.variant']);

        $totalQuantity = 0;
        $totalPrice = 0;

        foreach ($cart->items as $item) {
            $totalQuantity += $item->quantity;
            $price = $item->variant ? $item->variant->price : ($item->product->sale_price ?? $item->product->base_price);
            $totalPrice += $price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart_id' => $cart->id,
                'items' => $cart->items,
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/cart/add",
     *     summary="Thêm sản phẩm vào giỏ hàng",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="Session ID cho khách vãng lai",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="product_variant_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="quantity", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Thêm sản phẩm thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã thêm sản phẩm vào giỏ hàng."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ / Hết hàng"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại hoặc đã bị xóa.'], 404);
        }
        $variantId = $request->product_variant_id;
        $quantity = $request->quantity;

        // Verify stock
        if ($variantId) {
            $variant = ProductVariant::findOrFail($variantId);
            if ($variant->product_id !== $product->id) {
                return response()->json(['message' => 'Biến thể không thuộc về sản phẩm này.'], 400);
            }
            if ($variant->stock_quantity < $quantity) {
                 return response()->json(['message' => 'Không đủ số lượng tồn kho cho biến thể này.', 'available' => $variant->stock_quantity], 400);
            }
        } else {
            // If there's no variant, maybe we don't have stock management at product level, assuming unlimited or handle accordingly.
            // Let's assume it's valid if active.
            if (!$product->is_active) {
                return response()->json(['message' => 'Sản phẩm không hoạt động hoặc đã bị vô hiệu hóa.'], 400);
            }
        }

        $cart = $this->findOrCreateCart($request);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variantId)
            ->first();

        DB::beginTransaction();
        try {
            if ($cartItem) {
                // If adding more exceeds stock, prevent it
                $newQuantity = $cartItem->quantity + $quantity;
                if ($variantId) {
                    $variant = ProductVariant::find($variantId);
                    if ($variant->stock_quantity < $newQuantity) {
                         return response()->json(['message' => 'Không đủ số lượng tồn kho.', 'available' => $variant->stock_quantity], 400);
                    }
                }
                
                $cartItem->quantity = $newQuantity;
                $cartItem->save();
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
                'data' => $cartItem
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Thêm sản phẩm vào giỏ hàng thất bại.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/cart/update/{itemId}",
     *     summary="Cập nhật số lượng sản phẩm trong giỏ",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         description="ID của chi tiết giỏ hàng (CartItem ID)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="Session ID cho khách vãng lai",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật số lượng thành công."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Không đủ số lượng tồn kho"),
     *     @OA\Response(response=404, description="Không tìm thấy giỏ hàng hoặc sản phẩm")
     * )
     */
    public function update(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = $this->getCart($request);
        if (!$cart) {
            return response()->json(['message' => 'Không tìm thấy giỏ hàng.'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->first();
        if (!$cartItem) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng.'], 404);
        }

        $quantity = $request->quantity;

        // Verify stock
        if ($cartItem->product_variant_id) {
            $variant = ProductVariant::find($cartItem->product_variant_id);
            if ($variant && $variant->stock_quantity < $quantity) {
                return response()->json(['message' => 'Không đủ số lượng tồn kho.', 'available' => $variant->stock_quantity], 400);
            }
        }

        $cartItem->quantity = $quantity;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật số lượng thành công.',
            'data' => $cartItem
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/cart/remove/{itemId}",
     *     summary="Xóa 1 sản phẩm khỏi giỏ hàng",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         description="ID của chi tiết giỏ hàng (CartItem ID)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="Session ID cho khách vãng lai",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã xóa sản phẩm khỏi giỏ hàng.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Không tìm thấy giỏ hàng hoặc sản phẩm")
     * )
     */
    public function remove(Request $request, $itemId)
    {
        $cart = $this->getCart($request);
        if (!$cart) {
            return response()->json(['message' => 'Không tìm thấy giỏ hàng.'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->first();
        if (!$cartItem) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng.'], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/cart/clear",
     *     summary="Xóa toàn bộ giỏ hàng",
     *     tags={"Cart"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-Session-ID",
     *         in="header",
     *         description="Session ID cho khách vãng lai",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200, 
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đã xóa tất cả sản phẩm trong giỏ hàng.")
     *         )
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $cart = $this->getCart($request);
        if (!$cart) {
            return response()->json(['success' => true, 'message' => 'Giỏ hàng đã trống sẵn.']);
        }

        CartItem::where('cart_id', $cart->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tất cả sản phẩm trong giỏ hàng.'
        ]);
    }

    /**
     * Helper to get existing cart.
     */
    private function getCart(Request $request)
    {
        $user = auth('api')->user();
        if ($user) {
            return Cart::where('user_id', $user->id)->first();
        }

        $sessionId = $request->header('X-Session-ID') ?? $request->session_id;
        if ($sessionId) {
            return Cart::where('session_id', $sessionId)->first();
        }

        return null;
    }

    /**
     * Helper to find or create a cart.
     */
    private function findOrCreateCart(Request $request)
    {
        $user = auth('api')->user();
        if ($user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            // If there's also a session ID, maybe merge logic here. For simplicity, just use user cart.
            return $cart;
        }

        // Require session ID for guests
        $sessionId = $request->header('X-Session-ID') ?? $request->session_id;
        if (!$sessionId) {
            // Generate one if missing or fail? Let's just require it to be sent by frontend.
            abort(400, 'Người dùng chưa xác thực và không cung cấp X-Session-ID / session_id.');
        }

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }
}
