<?php

namespace App\Http\Controllers;

use App\Http\Requests\RevenueStatsRequest;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueStatsController extends Controller
{
    /**
     * Detect database driver để dùng SQL syntax phù hợp
     */
    private function getDatabaseDriver()
    {
        return config('database.default');
    }

    /**
     * Lấy SQL function cho giờ dựa trên database driver
     */
    private function getHourFunction()
    {
        return $this->getDatabaseDriver() === 'sqlite' ? "strftime('%H', created_at)" : "HOUR(created_at)";
    }

    /**
     * Lấy SQL function cho tháng dựa trên database driver
     */
    private function getMonthFunction()
    {
        return $this->getDatabaseDriver() === 'sqlite' ? "strftime('%m', created_at)" : "MONTH(created_at)";
    }

    /**
     * Lấy SQL function cho quý dựa trên database driver
     */
    private function getQuarterFunction()
    {
        if ($this->getDatabaseDriver() === 'sqlite') {
            return "
                CASE 
                    WHEN strftime('%m', created_at) BETWEEN '01' AND '03' THEN 1
                    WHEN strftime('%m', created_at) BETWEEN '04' AND '06' THEN 2
                    WHEN strftime('%m', created_at) BETWEEN '07' AND '09' THEN 3
                    ELSE 4
                END
            ";
        }
        return "QUARTER(created_at)";
    }
    /**
     * @OA\Get(
     *     path="/admin/revenue/stats",
     *     summary="Thống kê doanh thu - 4 mode linh hoạt",
     *     description="Thống kê doanh thu từ đơn hàng đã thanh toán (payment_status=paid).
     *     
     *     **Mode 1 - Theo ngày (DAY mode):**
     *     - GET /admin/revenue/stats?period=day&date=2026-04-07
     *     - Trả về doanh thu trong ngày, group by giờ
     *     
     *     **Mode 2 - Theo tháng (MONTH mode):**
     *     - GET /admin/revenue/stats?period=month&year=2026&month=4
     *     - Trả về doanh thu trong tháng 4/2026, group by ngày
     *     
     *     **Mode 3 - Theo quý (QUARTER mode):**
     *     - GET /admin/revenue/stats?period=quarter&year=2026&quarter=2
     *     - Trả về doanh thu trong quý 2/2026, group by tháng
     *     
     *     **Mode 4 - Theo năm (YEAR mode):**
     *     - GET /admin/revenue/stats?period=year&year=2026
     *     - Trả về doanh thu trong năm 2026, group by quý",
     *     operationId="getRevenueStats",
     *     tags={"Revenue Stats"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=true,
     *         description="Kỳ thống kê: day | month | quarter | year",
     *         @OA\Schema(type="string", enum={"day", "month", "quarter", "year"}, example="month")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Bắt buộc khi period=day. Định dạng Y-m-d",
     *         @OA\Schema(type="string", format="date", example="2026-04-07")
     *     ),
     *
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         description="Bắt buộc khi period=month|quarter|year. Năm cần thống kê",
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         description="Bắt buộc khi period=month. Tháng từ 1-12",
     *         @OA\Schema(type="integer", minimum=1, maximum=12, example=4)
     *     ),
     *
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         required=false,
     *         description="Bắt buộc khi period=quarter. Quý từ 1-4",
     *         @OA\Schema(type="integer", enum={1,2,3,4}, example=2)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="period", type="string", example="month"),
     *             @OA\Property(property="period_label", type="string", example="Tháng 4/2026"),
     *             @OA\Property(property="total_revenue", type="number", format="float", example=250000000),
     *             @OA\Property(property="total_orders", type="integer", example=450),
     *             @OA\Property(property="average_order_value", type="number", format="float", example=555555.56),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", example="2026-04-01"),
     *                     @OA\Property(property="label", type="string", example="01/04/2026"),
     *                     @OA\Property(property="revenue", type="number", format="float", example=5000000),
     *                     @OA\Property(property="order_count", type="integer", example=15),
     *                     @OA\Property(property="average_order_value", type="number", format="float", example=333333.33)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Dữ liệu validation không hợp lệ"),
     *     @OA\Response(response=401, description="Chưa xác thực")
     * )
     */
    public function stats(RevenueStatsRequest $request)
    {
        $period = $request->input('period');

        return match ($period) {
            'day' => $this->getStatsByDay($request),
            'month' => $this->getStatsByMonth($request),
            'quarter' => $this->getStatsByQuarter($request),
            'year' => $this->getStatsByYear($request),
            default => response()->json(['success' => false, 'message' => 'Invalid period'], 400)
        };
    }

    /**
     * Mode 1: Thống kê theo ngày
     * Input: date (Y-m-d)
     * Output: Group by giờ
     */
    private function getStatsByDay($request)
    {
        $date = $request->input('date');
        $dateCarbon = Carbon::createFromFormat('Y-m-d', $date);
        $startOfDay = $dateCarbon->startOfDay();
        $endOfDay = $dateCarbon->endOfDay();

        $query = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        // Database agnostic: sử dụng helper function
        $data = $query
            ->selectRaw($this->getHourFunction() . " as hour, SUM(total_amount) as revenue, COUNT(*) as order_count")
            ->groupByRaw($this->getHourFunction())
            ->orderBy('hour')
            ->get();

        $formattedData = $data->map(function ($item) {
            return [
                'date' => str_pad($item->hour, 2, '0', STR_PAD_LEFT),
                'label' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00 - ' . str_pad($item->hour + 1, 2, '0', STR_PAD_LEFT) . ':00',
                'revenue' => round((float)$item->revenue, 2),
                'order_count' => (int)$item->order_count,
                'average_order_value' => $item->order_count > 0 ? round($item->revenue / $item->order_count, 2) : 0,
            ];
        })->toArray();

        $totalRevenue = array_sum(array_column($formattedData, 'revenue'));
        $totalOrders = array_sum(array_column($formattedData, 'order_count'));

        return response()->json([
            'success' => true,
            'period' => 'day',
            'period_label' => $dateCarbon->format('d/m/Y'),
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'data' => $formattedData
        ]);
    }

    /**
     * Mode 2: Thống kê theo tháng
     * Input: year, month
     * Output: Group by ngày
     */
    private function getStatsByMonth($request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        $query = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Database agnostic: sử dụng helper function
        $data = $query
            ->selectRaw("DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as order_count")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $formattedData = $data->map(function ($item) {
            $itemDate = Carbon::createFromFormat('Y-m-d', $item->date);
            return [
                'date' => $item->date,
                'label' => $itemDate->format('d/m/Y'),
                'revenue' => round((float)$item->revenue, 2),
                'order_count' => (int)$item->order_count,
                'average_order_value' => $item->order_count > 0 ? round($item->revenue / $item->order_count, 2) : 0,
            ];
        })->toArray();

        $totalRevenue = array_sum(array_column($formattedData, 'revenue'));
        $totalOrders = array_sum(array_column($formattedData, 'order_count'));

        return response()->json([
            'success' => true,
            'period' => 'month',
            'period_label' => 'Tháng ' . $month . '/' . $year,
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'data' => $formattedData
        ]);
    }

    /**
     * Mode 3: Thống kê theo quý
     * Input: year, quarter (1,2,3,4)
     * Output: Group by tháng
     */
    private function getStatsByQuarter($request)
    {
        $year = $request->input('year');
        $quarter = $request->input('quarter');

        // Tính range tháng theo quý
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth();

        $query = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $data = $query
            ->selectRaw($this->getMonthFunction() . " as month, SUM(total_amount) as revenue, COUNT(*) as order_count")
            ->groupByRaw($this->getMonthFunction())
            ->orderBy('month')
            ->get();

        $formattedData = $data->map(function ($item) use ($year) {
            $monthDate = Carbon::createFromDate($year, $item->month, 1);
            return [
                'date' => $year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'label' => 'Tháng ' . $item->month . '/' . $year,
                'revenue' => round((float)$item->revenue, 2),
                'order_count' => (int)$item->order_count,
                'average_order_value' => $item->order_count > 0 ? round($item->revenue / $item->order_count, 2) : 0,
            ];
        })->toArray();

        $totalRevenue = array_sum(array_column($formattedData, 'revenue'));
        $totalOrders = array_sum(array_column($formattedData, 'order_count'));

        return response()->json([
            'success' => true,
            'period' => 'quarter',
            'period_label' => 'Quý ' . $quarter . ' năm ' . $year,
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'data' => $formattedData
        ]);
    }

    /**
     * Mode 4: Thống kê theo năm
     * Input: year
     * Output: Group by quý
     */
    private function getStatsByYear($request)
    {
        $year = $request->input('year');

        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();

        $query = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $data = $query
            ->selectRaw($this->getQuarterFunction() . " as quarter, SUM(total_amount) as revenue, COUNT(*) as order_count")
            ->groupByRaw($this->getQuarterFunction())
            ->orderBy('quarter')
            ->get();

        $formattedData = $data->map(function ($item) use ($year) {
            return [
                'date' => $year . '-Q' . $item->quarter,
                'label' => 'Quý ' . $item->quarter . ' năm ' . $year,
                'revenue' => round((float)$item->revenue, 2),
                'order_count' => (int)$item->order_count,
                'average_order_value' => $item->order_count > 0 ? round($item->revenue / $item->order_count, 2) : 0,
            ];
        })->toArray();

        $totalRevenue = array_sum(array_column($formattedData, 'revenue'));
        $totalOrders = array_sum(array_column($formattedData, 'order_count'));

        return response()->json([
            'success' => true,
            'period' => 'year',
            'period_label' => 'Năm ' . $year,
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'data' => $formattedData
        ]);
    }
}
