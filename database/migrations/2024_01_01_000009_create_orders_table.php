<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('order_code', 30)->unique()->comment('Mã đơn hàng');
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 15);
            $table->string('shipping_address', 500)->comment('Địa chỉ giao hàng (snapshot)');
            $table->decimal('subtotal', 15, 2)->comment('Tổng tiền hàng trước giảm');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->comment('Tổng thanh toán');
            $table->enum('payment_method', ['cod', 'bank_transfer', 'momo', 'vnpay']);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('order_status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'returned'
            ])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
