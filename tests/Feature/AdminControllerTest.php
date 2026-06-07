<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'token', 'admin']);
    }

    public function test_admin_can_get_profile()
    {
        $admin = Admin::factory()->create();
        $token = auth('admin-api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/me');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'admin' => ['id' => $admin->id]]);
    }

    public function test_superadmin_can_list_staff()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        Admin::factory()->count(3)->create();
        
        $token = auth('admin-api')->login($superadmin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/staff');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(4, 'data');
    }

    public function test_admin_cannot_list_staff()
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        $token = auth('admin-api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/staff');

        $response->assertStatus(403);
    }

    public function test_superadmin_can_create_staff()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        $token = auth('admin-api')->login($superadmin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/staff', [
                'name' => 'New Staff',
                'email' => 'staff@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'staff',
                'is_active' => 1
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Tạo tài khoản quản trị thành công']);

        $this->assertDatabaseHas('admins', ['email' => 'staff@example.com']);
    }

    public function test_superadmin_can_update_staff()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        $staff = Admin::factory()->create(['role' => 'staff']);
        $token = auth('admin-api')->login($superadmin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/staff/{$staff->id}", [
                'name' => 'Updated Name',
                'role' => 'admin'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Cập nhật tài khoản thành công']);

        $this->assertDatabaseHas('admins', ['id' => $staff->id, 'name' => 'Updated Name', 'role' => 'admin']);
    }

    public function test_superadmin_can_delete_staff()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        $staff = Admin::factory()->create(['role' => 'staff']);
        $token = auth('admin-api')->login($superadmin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/staff/{$staff->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Xóa tài khoản thành công']);

        $this->assertSoftDeleted('admins', ['id' => $staff->id]);
    }

    public function test_superadmin_cannot_delete_self()
    {
        $superadmin = Admin::factory()->superadmin()->create();
        $token = auth('admin-api')->login($superadmin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/staff/{$superadmin->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Bạn không thể tự xóa tài khoản superadmin của chính mình']);
    }
}
