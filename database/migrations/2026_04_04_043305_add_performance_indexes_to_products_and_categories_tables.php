<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm composite indexes để tối ưu performance cho các truy vấn filter/sort thường dùng
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Composite index cho trường hợp phổ biến nhất: lọc active + sort created_at
            $table->index(['is_active', 'created_at']);

            // Index cho filter giá
            $table->index(['is_active', 'base_price']);

            // Index cho filter category + active
            $table->index(['category_id', 'is_active']);
        });

        Schema::table('categories', function (Blueprint $table) {
            // Index cho hàm getAllChildCategoryIds() - truy vấn theo parent_id
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'created_at']);
            $table->dropIndex(['is_active', 'base_price']);
            $table->dropIndex(['category_id', 'is_active']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
        });
    }
};
