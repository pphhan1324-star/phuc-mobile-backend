<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_index_returns_user_addresses()
    {
        UserAddress::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/user/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_store_creates_new_address()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/user/addresses', [
                'receiver_name' => 'Nguyễn Văn A',
                'receiver_phone' => '0987654321',
                'province_id' => 1,
                'district_id' => 1,
                'ward_id' => 1,
                'address_detail' => '123 ABC Street',
                'type' => 'home',
                'is_default' => false
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'receiver_name' => 'Nguyễn Văn A'
        ]);
    }

    public function test_first_address_becomes_default_automatically()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/user/addresses', [
                'receiver_name' => 'First Address',
                'receiver_phone' => '0987654321',
                'province_id' => 1,
                'district_id' => 1,
                'ward_id' => 1,
                'address_detail' => 'Address Detail',
                'type' => 'home',
                'is_default' => false // Should be overridden to true if first
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_addresses', [
            'receiver_name' => 'First Address',
            'is_default' => true
        ]);
    }

    public function test_update_modifies_existing_address()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/user/addresses/{$address->id}", [
                'receiver_name' => 'Updated Name'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'receiver_name' => 'Updated Name'
        ]);
    }

    public function test_destroy_deletes_address()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_set_default_updates_default_address()
    {
        $oldDefault = UserAddress::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $newDefault = UserAddress::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/user/addresses/{$newDefault->id}/set-default");

        $response->assertStatus(200);
        
        $this->assertTrue($newDefault->fresh()->is_default);
        $this->assertFalse($oldDefault->fresh()->is_default);
    }
}
