<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // --- Thông tin thêm ---
            $table->string('image_url')->nullable()->after('sku')
                ->comment('Ảnh riêng của biến thể này');
            $table->boolean('is_available')->default(true)->after('image_url')
                ->comment('Biến thể này còn hàng/còn sản xuất không?');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'image_url',
                'is_available',
            ]);
        });
    }
};

