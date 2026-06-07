<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Đã chuyển sang bảng phone_specifications theo chuẩn 3NF
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không thực hiện gì vì up() trống
    }
};
