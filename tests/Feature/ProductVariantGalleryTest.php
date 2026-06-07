<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductVariantGalleryTest extends TestCase
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

    public function test_admin_can_create_variant_with_gallery()
    {
        $product = Product::factory()->create();
        
        $response = $this->withAuth()->postJson("/api/products/{$product->id}/variants", [
            'sku' => 'VAR-01-RED',
            'price' => 1500000,
            'color' => 'Red',
            'image' => UploadedFile::fake()->image('variant.jpg'),
            'gallery_images' => [
                UploadedFile::fake()->image('vg1.png'),
                UploadedFile::fake()->image('vg2.jpg'),
            ]
        ]);

        $response->assertStatus(201);
        
        $variant = ProductVariant::first();
        $this->assertNotNull($variant->getRawOriginal('image_url'));
        Storage::disk('public')->assertExists($variant->getRawOriginal('image_url'));
        
        // Gallery images associated with variant
        $this->assertCount(2, $variant->images);
        foreach ($variant->images as $image) {
            $this->assertEquals($variant->id, $image->product_variant_id);
            Storage::disk('public')->assertExists($image->getRawOriginal('image_url'));
        }
    }

    public function test_admin_can_update_variant_gallery()
    {
        $variant = ProductVariant::factory()->create();
        $galleryItem = ProductImage::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'image_url' => 'variants/vg_old.webp',
            'is_primary' => false,
            'sort_order' => 0
        ]);
        Storage::disk('public')->put('variants/vg_old.webp', 'dummy');

        $response = $this->withAuth()->postJson("/api/products/{$variant->product_id}/variants/{$variant->id}", [
            '_method' => 'PUT',
            'color' => 'Blue',
            'delete_gallery_ids' => json_encode([$galleryItem->id]),
            'gallery_images' => [
                UploadedFile::fake()->image('vg_new.jpg')
            ]
        ]);

        $response->assertStatus(200);
        
        $variant->refresh();
        Storage::disk('public')->assertMissing('variants/vg_old.webp');
        $this->assertDatabaseMissing('product_images', ['id' => $galleryItem->id]);
        
        $this->assertCount(1, $variant->images);
        Storage::disk('public')->assertExists($variant->images->first()->getRawOriginal('image_url'));
    }

    public function test_deleting_variant_removes_variant_specific_images()
    {
        $variant = ProductVariant::factory()->create(['image_url' => 'variants/v1.webp']);
        Storage::disk('public')->put('variants/v1.webp', 'content');
        
        $gallery = $variant->images()->create([
            'product_id' => $variant->product_id,
            'image_url' => 'variants/vg1.webp',
            'is_primary' => false,
            'sort_order' => 0
        ]);
        Storage::disk('public')->put('variants/vg1.webp', 'content');

        // Another image for the product but NOT for this variant
        $otherImage = ProductImage::create([
            'product_id' => $variant->product_id,
            'image_url' => 'products/p1.webp',
            'is_primary' => false,
            'sort_order' => 0
        ]);
        Storage::disk('public')->put('products/p1.webp', 'content');

        $response = $this->withAuth()->deleteJson("/api/products/{$variant->product_id}/variants/{$variant->id}");
        $response->assertStatus(200);
        
        // Variant images should be gone
        Storage::disk('public')->assertMissing('variants/v1.webp');
        Storage::disk('public')->assertMissing('variants/vg1.webp');
        $this->assertDatabaseMissing('product_images', ['id' => $gallery->id]);
        
        // Variant should be soft deleted
        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
        
        // Other product images should REMAIN
        Storage::disk('public')->assertExists('products/p1.webp');
        $this->assertDatabaseHas('product_images', ['id' => $otherImage->id]);
    }
}
