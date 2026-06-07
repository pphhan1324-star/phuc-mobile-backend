<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_categories_list()
    {
        Category::factory()->count(5)->create(['is_active' => true]);
        Category::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_index_can_return_inactive_categories_for_admin()
    {
        Category::factory()->count(5)->create(['is_active' => true]);
        Category::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/categories?all=1');

        $response->assertStatus(200)
            ->assertJsonCount(7);
    }

    public function test_index_returns_tree_structure()
    {
        $parent = Category::factory()->create();
        Category::factory()->count(3)->create(['parent_id' => $parent->id]);

        $response = $this->getJson('/api/categories?tree=1');

        $response->assertStatus(200);
        
        // Kiểm tra xem parent có children không
        $responseData = $response->json();
        $parentData = collect($responseData)->where('id', $parent->id)->first();
        
        $this->assertNotNull($parentData);
        $this->assertCount(3, $parentData['children']);
    }

    public function test_show_returns_category_details()
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        $token = auth('admin-api')->login($admin);
        $category = Category::factory()->create();
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $category->id, 'name' => $category->name]);
    }

    public function test_admin_can_store_category()
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        $token = auth('admin-api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/categories', [
                'name' => 'New Category',
                'description' => 'Description test',
                'is_active' => 1,
                'sort_order' => 1
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categories', ['name' => 'New Category']);
    }

    public function test_regular_user_cannot_store_category()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/categories', [
                'name' => 'New Category'
            ]);

        $response->assertStatus(401); // Unauthenticated for admin-api guard
    }

    public function test_admin_can_update_category()
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        $token = auth('admin-api')->login($admin);
        $category = Category::factory()->create(['name' => 'Old Name']);

        // Laravel requires POST with _method=PUT for multipart/form-data simulation if needed, 
        // but here it's simple JSON update usually. 
        // Controller handle both.
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/categories/{$category->id}", [
                '_method' => 'PUT',
                'name' => 'Updated Name'
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_empty_category()
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        $token = auth('admin-api')->login($admin);
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_category_with_products()
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        $token = auth('admin-api')->login($admin);
        
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
            
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
