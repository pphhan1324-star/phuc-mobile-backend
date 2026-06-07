<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm indexes tối ưu performance cho orders, cart_items, users
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Index cho admin filter theo trạng thái đơn hàng
            $table->index('order_status');
            // Composite index: user xem lịch sử đơn hàng + filter trạng thái
            $table->index(['user_id', 'order_status']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            // Composite index: tìm duplicate item khi thêm vào giỏ
            $table->index(['cart_id', 'product_id', 'product_variant_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Index cho sorting theo thời gian tạo (admin list users)
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_status']);
            $table->dropIndex(['user_id', 'order_status']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['cart_id', 'product_id', 'product_variant_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
