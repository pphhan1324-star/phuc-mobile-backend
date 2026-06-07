<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use App\Models\Order;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$start = Carbon::createFromFormat('Y-m-d', '2026-04-07')->startOfDay();
$end = Carbon::createFromFormat('Y-m-d', '2026-04-07')->endOfDay();

$orders = Order::where('payment_status', 'paid')
    ->whereBetween('created_at', [$start, $end])
    ->get();

echo "Orders found: " . $orders->count() . "\n";

foreach ($orders as $order) {
    echo sprintf(
        "%d | %s | %s | %s | %s | %s\n",
        $order->id,
        $order->order_code,
        $order->payment_status,
        $order->order_status,
        $order->created_at->format('Y-m-d H:i:s'),
        $order->total_amount
    );
}

$data = Order::where('payment_status', 'paid')
    ->whereBetween('created_at', [$start, $end])
    ->selectRaw("strftime('%H', created_at) as hour, SUM(total_amount) as revenue, COUNT(*) as order_count")
    ->groupByRaw("strftime('%H', created_at)")
    ->orderBy('hour')
    ->get();

echo "Data count: " . $data->count() . "\n";

if ($data->count() > 0) {
    echo "Hour: " . $data[0]->hour . ", Revenue: " . $data[0]->revenue . "\n";
}