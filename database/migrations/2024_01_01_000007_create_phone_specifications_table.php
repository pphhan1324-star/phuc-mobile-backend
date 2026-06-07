<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phone_specifications', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained('products')->cascadeOnDelete();
            $table->string('screen_size', 50)->nullable()->comment('Kích thước màn hình');
            $table->string('screen_tech', 100)->nullable()->comment('Công nghệ màn hình');
            $table->string('rear_camera', 150)->nullable()->comment('Camera sau');
            $table->string('front_camera', 100)->nullable()->comment('Camera trước');
            $table->string('chipset', 100)->nullable()->comment('Vi xử lý (CPU)');
            $table->string('battery', 50)->nullable()->comment('Dung lượng pin');
            $table->string('charging_speed', 50)->nullable()->comment('Tốc độ sạc');
            $table->string('operating_system', 50)->nullable()->comment('Hệ điều hành');
            $table->unsignedInteger('weight_g')->nullable()->comment('Trọng lượng (gram)');
            $table->string('material', 100)->nullable()->comment('Chất liệu vỏ máy');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_specifications');
    }
};
