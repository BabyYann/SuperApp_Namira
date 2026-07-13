<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageHelper
{
    /**
     * Upload, resize, and convert an image to WebP format using GD.
     *
     * @param UploadedFile $file The uploaded file object
     * @param string $directory Subdirectory inside the public disk (e.g. 'testimonials')
     * @param int $maxWidth The maximum width allowed for the image
     * @param int $quality Quality factor for WebP conversion (0-100)
     * @return string|null Path to the stored WebP file, or null on failure
     */
    public static function uploadAndConvert(UploadedFile $file, string $directory, int $maxWidth, int $quality = 80): ?string
    {
        // 1. Get original image resources based on MIME type
        $mime = $file->getMimeType();
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = @imagecreatefromjpeg($file->getRealPath());
                break;
            case 'image/png':
                $source = @imagecreatefrompng($file->getRealPath());
                if ($source) {
                    imagealphablending($source, false);
                    imagesavealpha($source, true);
                }
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($file->getRealPath());
                break;
            case 'image/webp':
                $source = @imagecreatefromwebp($file->getRealPath());
                break;
            default:
                // Fallback to standard upload if not a supported GD image type
                $path = $file->store($directory, 'public');
                return 'storage/' . $path;
        }

        if (!$source) {
            // Fallback in case image creation failed (e.g. file is corrupted or GD fails)
            $path = $file->store($directory, 'public');
            return 'storage/' . $path;
        }

        // 2. Calculate new dimensions keeping the aspect ratio
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);

        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) (($origHeight / $origWidth) * $maxWidth);
        }

        // 3. Create a new true color image canvas
        $target = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for WebP
        imagealphablending($target, false);
        imagesavealpha($target, true);
        
        // Handle alpha channel transparent background
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefill($target, 0, 0, $transparent);

        // 4. Resample/Resize the image
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // 5. Generate a unique name with .webp extension
        $filename = uniqid() . '_' . time() . '.webp';
        $tempPath = tempnam(sys_get_temp_dir(), 'webp_');
        
        // 6. Save as webp to temp directory
        imagewebp($target, $tempPath, $quality);

        // 7. Store it to Laravel public disk
        $finalPath = $directory . '/' . $filename;
        Storage::disk('public')->putFileAs($directory, new \Illuminate\Http\File($tempPath), $filename);

        // 8. Clean up resources
        imagedestroy($source);
        imagedestroy($target);
        @unlink($tempPath);

        return 'storage/' . $finalPath;
    }
}
