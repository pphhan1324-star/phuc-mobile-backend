<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageServiceTest extends TestCase
{
    protected $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageService = new ImageService();
        Storage::fake('public');
    }

    public function test_it_uploads_and_processes_image_to_webp()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        
        $path = $this->imageService->uploadAndProcess($file, 'products');
        
        $this->assertNotNull($path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_it_resizes_large_images_to_1600px()
    {
        // Create a horizontal image 2000x1000
        $file = UploadedFile::fake()->image('large.jpg', 2000, 1000);
        
        $path = $this->imageService->uploadAndProcess($file, 'products', 1600);
        
        $this->assertNotNull($path);
        
        // Verify dimensions of processed image
        $manager = new ImageManager(new Driver());
        $image = $manager->decode(Storage::disk('public')->get($path));
        
        $this->assertEquals(1600, $image->width());
        $this->assertEquals(800, $image->height()); // Aspect ratio 2000:1000 = 2:1 -> 1600:800
    }

    public function test_it_keeps_small_images_original_size()
    {
        $file = UploadedFile::fake()->image('small.png', 800, 600);
        
        $path = $this->imageService->uploadAndProcess($file, 'products', 1600);
        
        $manager = new ImageManager(new Driver());
        $image = $manager->decode(Storage::disk('public')->get($path));
        
        $this->assertEquals(800, $image->width());
        $this->assertEquals(600, $image->height());
    }

    public function test_it_deletes_image_from_storage()
    {
        Storage::disk('public')->put('products/temp.webp', 'content');
        
        $result = $this->imageService->deleteImage('products/temp.webp');
        
        $this->assertTrue($result);
        Storage::disk('public')->assertMissing('products/temp.webp');
    }
}
