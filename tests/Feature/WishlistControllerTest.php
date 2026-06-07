<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WishlistControllerTest extends TestCase
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

    protected function headers()
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    /** @test */
    public function index_returns_user_wishlist()
    {
        $products = Product::factory()->count(3)->create();
        foreach ($products as $product) {
            Wishlist::create([
                'user_id' => $this->user->id,
                'product_id' => $product->id
            ]);
        }

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/wishlist');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Lấy danh sách yêu thích thành công!')
            ->assertJsonStructure(['data' => ['data']]);
        
        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function index_filters_by_search_and_category()
    {
        $category = \App\Models\Category::factory()->create();
        $product1 = Product::factory()->create(['name' => 'Matching Product', 'category_id' => $category->id]);
        $product2 = Product::factory()->create(['name' => 'Other Product']);

        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $product1->id]);
        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $product2->id]);

        $response = $this->withHeaders($this->headers())
            ->getJson("/api/wishlist?search=Matching&category_id={$category->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Matching Product', $response->json('data.data.0.product.name'));
    }

    /** @test */
    public function store_adds_product_to_wishlist()
    {
        $product = Product::factory()->create();

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/wishlist/add', [
                'product_id' => $product->id
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Sản phẩm đã được thêm vào danh sách yêu thích thành công!',
            ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id
        ]);
    }

    /** @test */
    public function store_prevents_duplicate_wishlist_items()
    {
        $product = Product::factory()->create(['is_active' => 1]);

        // Thêm lần 1
        $this->withHeaders($this->headers())
            ->postJson('/api/wishlist/add', ['product_id' => $product->id]);

        // Thêm lần 2
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/wishlist/add', ['product_id' => $product->id]);

        $response->assertStatus(201);
        
        // Kiểm tra DB chỉ có 1 bản ghi
        $this->assertEquals(1, Wishlist::where('user_id', $this->user->id)
            ->where('product_id', $product->id)
            ->count());
    }

    /** @test */
    public function store_fails_if_product_is_inactive()
    {
        $product = Product::factory()->create(['is_active' => 0]);

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/wishlist/add', ['product_id' => $product->id]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    /** @test */
    public function remove_deletes_product_from_wishlist()
    {
        $product = Product::factory()->create();
        Wishlist::create(['user_id' => $this->user->id, 'product_id' => $product->id]);

        $response = $this->withHeaders($this->headers())
            ->deleteJson("/api/wishlist/remove/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Đã xóa sản phẩm khỏi danh sách');

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id
        ]);
    }

    /** @test */
    public function clear_empties_user_wishlist()
    {
        Product::factory()->count(5)->create()->each(function ($product) {
            Wishlist::create(['user_id' => $this->user->id, 'product_id' => $product->id]);
        });

        $this->assertCount(5, Wishlist::where('user_id', $this->user->id)->get());

        $response = $this->withHeaders($this->headers())
            ->deleteJson('/api/wishlist/clear');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Đã xóa toàn bộ danh sách');

        $this->assertCount(0, Wishlist::where('user_id', $this->user->id)->get());
    }

    /** @test */
    public function unauthenticated_user_cannot_access_wishlist()
    {
        $response = $this->getJson('/api/wishlist');
        $response->assertStatus(401);
    }
}
