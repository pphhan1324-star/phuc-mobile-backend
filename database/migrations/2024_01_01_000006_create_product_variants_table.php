<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->foreignId('ram_id')->nullable()->constrained('rams')->nullOnDelete();
            $table->foreignId('storage_id')->nullable()->constrained('storages')->nullOnDelete();
            $table->decimal('price', 15, 2)->comment('Giá của biến thể');
            $table->unsignedInteger('stock_quantity')->default(0)->comment('Số lượng tồn kho');
            $table->string('sku', 120)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
