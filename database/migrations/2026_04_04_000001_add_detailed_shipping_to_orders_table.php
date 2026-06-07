<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->after('receiver_phone')->nullable()->comment('ID Tỉnh/Thành');
            $table->unsignedBigInteger('district_id')->after('province_id')->nullable()->comment('ID Quận/Huyện');
            $table->unsignedBigInteger('ward_id')->after('district_id')->nullable()->comment('ID Phường/Xã');
            $table->string('address_detail', 255)->after('ward_id')->nullable()->comment('Địa chỉ cụ thể (sau này ghép lại thành shipping_address)');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['province_id', 'district_id', 'ward_id', 'address_detail']);
        });
    }
};
