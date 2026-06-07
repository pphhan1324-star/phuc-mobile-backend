<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OrderItem;
use App\Http\Requests\ProductStatsRequest;

class ProductStatsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/products/stats/by-price",
     *     summary="Thống kê sản phẩm theo khoảng giá",
     *     description="Trả về thống kê số lượng sản phẩm, lượng bán và doanh thu của từng khoảng giá",
     *     operationId="getProductStatsByPrice",
     *     tags={"Product Stats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Số ngày thống kê từ ngày hiện tại trở lại (mặc định: 30)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=365, default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period_days", type="integer", example=30),
     *                 @OA\Property(property="price_ranges", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="range", type="string", example="0 - 1,000,000"),
     *                         @OA\Property(property="min_price", type="number"),
     *                         @OA\Property(property="max_price", type="number"),
     *                         @OA\Property(property="product_count", type="integer"),
     *                         @OA\Property(property="total_quantity_sold", type="integer"),
     *                         @OA\Property(property="total_revenue", type="number"),
     *                         @OA\Property(property="average_selling_price", type="number")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function byPrice(ProductStatsRequest $request)
    {
        try {
            $days = $request->input('days', 30);
            $startDate = now()->subDays($days)->startOfDay();

            // Định nghĩa các khoảng giá
            $priceRanges = [
                ['min' => 0, 'max' => 1000000, 'label' => '0 - 1,000,000'],
                ['min' => 1000000, 'max' => 5000000, 'label' => '1,000,000 - 5,000,000'],
                ['min' => 5000000, 'max' => 10000000, 'label' => '5,000,000 - 10,000,000'],
                ['min' => 10000000, 'max' => 20000000, 'label' => '10,000,000 - 20,000,000'],
                ['min' => 20000000, 'max' => PHP_INT_MAX, 'label' => 'Trên 20,000,000'],
            ];

            $stats = [];

            foreach ($priceRanges as $range) {
                // Lấy sản phẩm trong khoảng giá
                $products = Product::whereBetween('base_price', [$range['min'], $range['max']])
                    ->where('is_active', true)
                    ->get();

                $productIds = $products->pluck('id')->toArray();

                // Thống kê bán hàng
                $sales = OrderItem::whereIn('product_id', $productIds)
                    ->whereHas('order', function ($query) use ($startDate) {
                        $query->where('created_at', '>=', $startDate)
                            ->where('order_status', '!=', 'cancelled');
                    })
                    ->selectRaw('SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
                    ->first();

                $totalQuantity = $sales?->total_quantity ?? 0;
                $totalRevenue = $sales?->total_revenue ?? 0;

                $stats[] = [
                    'range' => $range['label'],
                    'min_price' => $range['min'],
                    'max_price' => $range['max'],
                    'product_count' => count($productIds),
                    'total_quantity_sold' => $totalQuantity,
                    'total_revenue' => $totalRevenue,
                    'average_selling_price' => $totalQuantity > 0 ? round($totalRevenue / $totalQuantity, 2) : 0,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'price_ranges' => $stats,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy thống kê theo giá: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/admin/products/stats/by-brand",
     *     summary="Thống kê sản phẩm theo brand",
     *     description="Trả về thống kê số lượng sản phẩm, lượng bán, doanh thu và lượt xem của từng brand",
     *     operationId="getProductStatsByBrand",
     *     tags={"Product Stats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Số lượng brand trả về (mặc định: 20)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Số ngày thống kê từ ngày hiện tại trở lại (mặc định: 30)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=365, default=30)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sắp xếp theo: quantity (số lượng bán), revenue (doanh thu), views (lượt xem)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"quantity","revenue","views"}, default="revenue")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period_days", type="integer", example=30),
     *                 @OA\Property(property="sort_by", type="string", example="revenue"),
     *                 @OA\Property(property="brands", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="brand", type="string"),
     *                         @OA\Property(property="product_count", type="integer"),
     *                         @OA\Property(property="total_views", type="integer"),
     *                         @OA\Property(property="total_quantity_sold", type="integer"),
     *                         @OA\Property(property="total_revenue", type="number"),
     *                         @OA\Property(property="average_price", type="number")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Lỗi validation"
     *     )
     * )
     */
    public function byBrand(ProductStatsRequest $request)
    {
        try {
            $limit = $request->input('limit', 20);
            $days = $request->input('days', 30);
            $sortBy = $request->input('sort_by', 'revenue');

            $startDate = now()->subDays($days)->startOfDay();

            // Lấy danh sách brand
            $brands = Product::where('is_active', true)
                ->whereNotNull('brand')
                ->distinct()
                ->pluck('brand');

            $brandStats = [];

            foreach ($brands as $brand) {
                // Lấy sản phẩm của brand này
                $products = Product::where('brand', $brand)
                    ->where('is_active', true)
                    ->get();

                $productIds = $products->pluck('id')->toArray();

                // Thống kê
                $sales = OrderItem::whereIn('product_id', $productIds)
                    ->whereHas('order', function ($query) use ($startDate) {
                        $query->where('created_at', '>=', $startDate)
                            ->where('order_status', '!=', 'cancelled');
                    })
                    ->selectRaw('SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
                    ->first();

                $totalViews = Product::where('brand', $brand)
                    ->where('is_active', true)
                    ->sum('view_count');

                $totalQuantity = $sales?->total_quantity ?? 0;
                $totalRevenue = $sales?->total_revenue ?? 0;

                $brandStats[] = [
                    'brand' => $brand,
                    'product_count' => count($productIds),
                    'total_views' => $totalViews,
                    'total_quantity_sold' => $totalQuantity,
                    'total_revenue' => $totalRevenue,
                    'average_price' => $totalQuantity > 0 ? round($totalRevenue / $totalQuantity, 2) : 0,
                ];
            }

            // Sắp xếp
            usort($brandStats, function ($a, $b) use ($sortBy) {
                if ($sortBy === 'quantity') {
                    return $b['total_quantity_sold'] <=> $a['total_quantity_sold'];
                } elseif ($sortBy === 'views') {
                    return $b['total_views'] <=> $a['total_views'];
                } else {
                    return $b['total_revenue'] <=> $a['total_revenue'];
                }
            });

            // Giới hạn số lượng
            $brandStats = array_slice($brandStats, 0, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'sort_by' => $sortBy,
                    'brands' => $brandStats,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy thống kê theo brand: ' . $e->getMessage(),
            ], 500);
        }
    }
}
