<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        $this->admin = Admin::factory()->create(['role' => 'admin']);
        $this->token = JWTAuth::fromUser($this->admin);
    }

    protected function withAuth()
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    public function test_admin_can_create_product_with_primary_and_gallery_images()
    {
        $category = Category::factory()->create();
        
        $response = $this->withAuth()->postJson('/api/products/create', [
            'category_id' => $category->id,
            'name' => 'Sofa Luxury',
            'base_price' => 5000000,
            'sku' => 'SOFA-LX-01',
            'stock_quantity' => 10,
            'image' => UploadedFile::fake()->image('primary.jpg', 1800, 1200),
            'gallery_images' => [
                UploadedFile::fake()->image('gallery1.png', 1200, 800),
                UploadedFile::fake()->image('gallery2.jpg', 800, 600),
            ]
        ]);

        $response->assertStatus(201);
        
        $product = Product::first();
        $this->assertNotNull($product->getRawOriginal('image_url'));
        $this->assertStringEndsWith('.webp', $product->getRawOriginal('image_url'));
        
        // Verify primary image exists
        Storage::disk('public')->assertExists($product->getRawOriginal('image_url'));
        
        // Verify gallery images
        $this->assertCount(2, $product->images);
        foreach ($product->images as $image) {
            $this->assertStringEndsWith('.webp', $image->getRawOriginal('image_url'));
            Storage::disk('public')->assertExists($image->getRawOriginal('image_url'));
        }
    }

    public function test_admin_can_update_product_and_manage_gallery()
    {
        $product = Product::factory()->create();
        $oldImage = $product->getRawOriginal('image_url');
        
        // Create some existing gallery images
        $galleryItem = ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'products/gallery_old.webp',
            'is_primary' => false,
            'sort_order' => 0
        ]);
        Storage::disk('public')->put('products/gallery_old.webp', 'dummy');

        // Update product: change primary image and delete 1 gallery image, add 1 new gallery image
        $response = $this->withAuth()->postJson("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'name' => 'Updated Sofa',
            'image' => UploadedFile::fake()->image('new_primary.jpg'),
            'gallery_images' => [
                UploadedFile::fake()->image('new_gallery.jpg')
            ],
            'delete_gallery_ids' => json_encode([$galleryItem->id])
        ]);

        $response->assertStatus(200);
        
        $product->refresh();
        
        // Old primary image should be deleted
        if ($oldImage) {
            Storage::disk('public')->assertMissing($oldImage);
        }
        
        // Old gallery image should be deleted
        Storage::disk('public')->assertMissing('products/gallery_old.webp');
        $this->assertDatabaseMissing('product_images', ['id' => $galleryItem->id]);
        
        // New gallery image should exist
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->getRawOriginal('image_url'));
    }

    public function test_deleting_product_removes_physical_images()
    {
        $product = Product::factory()->create(['image_url' => 'products/p1.webp']);
        Storage::disk('public')->put('products/p1.webp', 'content');
        
        $gallery = $product->images()->create([
            'image_url' => 'products/g1.webp',
            'is_primary' => false,
            'sort_order' => 0
        ]);
        Storage::disk('public')->put('products/g1.webp', 'content');

        $response = $this->withAuth()->deleteJson("/api/products/{$product->id}");
        $response->assertStatus(200);
        
        Storage::disk('public')->assertMissing('products/p1.webp');
        Storage::disk('public')->assertMissing('products/g1.webp');
        
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $gallery->id]);
    }
}
