<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Product;
class SystemCoreFeatureTest extends TestCase
{

    /**
     * TC01: Test Đăng ký tài khoản thành công
     */
    public function test_user_can_register_successfully()
    {
        $uniqueEmail = 'phuc_test_' . time() . '@gmail.com';
        $response = $this->postJson('/api/register', [
            'name' => 'Phúc Tester',
            'email' => $uniqueEmail,
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'message', 'token', 'user']);

        $this->assertDatabaseHas('users', ['email' => $uniqueEmail]);
    }

    /**
     * TC01: Test Đăng nhập tài khoản thành công
     */
    public function test_user_can_login_successfully()
    {
        $email = 'login_test_' . time() . '@gmail.com';
        $user = User::create([
            'name' => 'Customer Test',
            'email' => $email,
            'password' => bcrypt('123456'),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => '123456'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'token', 'user']);
    }

    /**
     * TH01 / TH06: Test Chặn Đăng nhập đối với tài khoản BỊ KHÓA (is_active = false)
     */
    public function test_locked_user_cannot_login()
    {
        $email = 'locked_test_' . time() . '@gmail.com';
        $user = User::create([
            'name' => 'Locked User',
            'email' => $email,
            'password' => bcrypt('123456'),
            'is_active' => false // Tài khoản bị khóa
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => '123456'
        ]);

        $response->assertStatus(403)
                 ->assertJson(['success' => false]);
    }

    /**
     * TC07 / TH04: Test Validate Mã Giảm Giá
     */
    public function test_coupon_validation()
    {
        $code = 'TEST' . rand(100, 999);
        $coupon = Coupon::create([
            'code' => $code,
            'type' => 'fixed',
            'value' => 50000,
            'discount_amount' => 50000,
            'is_active' => true,
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(10),
            'usage_limit' => 10,
            'used_count' => 0,
            'min_order_amount' => 100000
        ]);

        $user = User::create([
            'name' => 'Coupon User',
            'email' => 'coupon_' . time() . '@gmail.com',
            'password' => bcrypt('123456'),
            'is_active' => true
        ]);

        // Đơn hàng đủ điều kiện
        $response = $this->actingAs($user, 'api')->postJson('/api/coupons/apply', [
            'code' => $code,
            'order_amount' => 200000
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }
}
