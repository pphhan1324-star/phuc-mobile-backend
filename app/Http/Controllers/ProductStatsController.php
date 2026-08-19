<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OrderItem;
use App\Http\Requests\ProductStatsRequest;

class ProductStatsController extends Controller
{
    /**
     * Thống kê Sản phẩm theo Thương hiệu (Brand).
     * Trả về số lượt xem, số lượng bán và doanh thu của từng hãng.
     */
    public function byBrand(ProductStatsRequest $request)
    {
        try {
            $limit = $request->input('limit', 20);
            $days = $request->input('days', 30);
            $sortBy = $request->input('sort_by', 'revenue');

            $startDate = now()->subDays($days)->startOfDay();

            // Lấy danh sách brand (Sử dụng model Brand 3NF)
            $brands = \App\Models\Brand::all();

            $brandStats = [];

            foreach ($brands as $brandModel) {
                $brandName = $brandModel->name;

                // Lấy sản phẩm của brand này
                $products = Product::where('brand_id', $brandModel->id)
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

                $totalViews = Product::where('brand_id', $brandModel->id)
                    ->where('is_active', true)
                    ->sum('view_count');

                $totalQuantity = $sales?->total_quantity ?? 0;
                $totalRevenue = $sales?->total_revenue ?? 0;

                $brandStats[] = [
                    'brand' => $brandName,
                    'logo' => $brandModel->logo,
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
