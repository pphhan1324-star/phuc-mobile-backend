<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageService
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Upload and process image (Resize to 1600px, Convert to WebP 85%)
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $maxWidth
     * @return string|null
     */
    public function uploadAndProcess(UploadedFile $file, string $directory = 'products', int $maxWidth = 1600): ?string
    {
        try {
            $fileName = $directory . '/' . uniqid() . '_' . time() . '.webp';
            
            $image = $this->manager->decode($file);
            
            // Resize if wider than maxWidth
            if ($image->width() > $maxWidth) {
                $image->scale(width: $maxWidth);
            }
            
            // Encode as WebP with 85% quality (good balance for furniture)
            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: 85);
            
            // Store in public disk
            Storage::disk('public')->put($fileName, (string) $encoded);
            
            // Clean up memory
            unset($image, $encoded);
            gc_collect_cycles();

            return $fileName;
        } catch (\Exception $e) {
            \Log::error("Image upload failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an image from storage
     *
     * @param string|null $path
     * @return bool
     */
    public function deleteImage(?string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }
}
