<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percent', 'fixed'])->comment('Loại giảm: % hoặc số tiền cố định');
            $table->decimal('value', 10, 2)->comment('Giá trị giảm');
            $table->decimal('min_order_amount', 15, 2)->nullable()->comment('Giá trị đơn hàng tối thiểu');
            $table->decimal('max_discount', 15, 2)->nullable()->comment('Mức giảm tối đa (cho loại %)');
            $table->unsignedInteger('usage_limit')->nullable()->comment('Số lần sử dụng tối đa');
            $table->unsignedInteger('used_count')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
