<!DOCTYPE html>
<html>
<head>
    <title>Hóa đơn đặt hàng</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #00b0d7; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #00b0d7; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .total { text-align: right; font-size: 1.2em; font-weight: bold; margin-top: 20px; color: #d9534f; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Phuc Mobile</h1>
            <p>Cảm ơn bạn đã mua sắm tại cửa hàng chúng tôi!</p>
        </div>
        
        <p>Xin chào <strong>{{ $order->receiver_name }}</strong>,</p>
        <p>Đơn hàng <strong>{{ $order->order_code }}</strong> của bạn đã được đặt thành công.</p>
        
        <h3>Thông tin giao hàng:</h3>
        <ul>
            <li>Người nhận: {{ $order->receiver_name }}</li>
            <li>Số điện thoại: {{ $order->receiver_phone }}</li>
            <li>Địa chỉ: {{ $order->shipping_address }}</li>
            <li>Phương thức thanh toán: {{ strtoupper($order->payment_method) }}</li>
        </ul>

        <h3>Chi tiết sản phẩm:</h3>
        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}<br>
                        <small>{{ $item->variant_info }}</small>
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                    <td>{{ number_format($item->subtotal, 0, ',', '.') }} đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            Tổng cộng: {{ number_format($order->total_amount, 0, ',', '.') }} đ
        </div>

        <div class="footer">
            <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi qua email hoặc hotline.</p>
            <p>&copy; {{ date('Y') }} Phuc Mobile. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
