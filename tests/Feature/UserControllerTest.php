<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo một admin để test các route quản lý user
        $this->admin = Admin::factory()->create([
            'role' => 'superadmin' // Hoặc admin tùy nhu cầu
        ]);
        $this->token = JWTAuth::fromUser($this->admin);
    }

    protected function headers()
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    /** @test */
    public function index_returns_list_of_users()
    {
        User::factory()->count(5)->create();

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'last_page',
                    'total'
                ]
            ]);
        
        $this->assertCount(5, $response->json('data.data'));
    }

    /** @test */
    public function show_returns_user_details()
    {
        $user = User::factory()->create(['name' => 'Specific User']);

        $response = $this->withHeaders($this->headers())
            ->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'Specific User');
    }

    /** @test */
    public function update_modifies_user_data()
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeaders($this->headers())
            ->putJson("/api/users/{$user->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Cập nhật người dùng thành công');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function destroy_removes_user()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->headers())
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Xóa người dùng thành công');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function unauthenticated_access_is_allowed_due_to_current_routes_config()
    {
        // Hiện tại route đang không được bảo vệ bởi middleware trong api.php
        $response = $this->getJson('/api/users');
        $response->assertStatus(200);
    }
}
