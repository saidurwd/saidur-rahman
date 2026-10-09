<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores a single uploaded photo as WebP on the public disk. Re-encoding drops all metadata
 * (EXIF, GPS, camera data) after applying the camera's rotation, and caps the width.
 */
class ImageStore
{
    public const DISK = 'public';

    /** @return string path relative to the public disk */
    public function store(UploadedFile $file, string $directory, int $maxWidth = 1600): string
    {
        $path = (string) $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            throw new RuntimeException('The file is not a readable image.');
        }

        $image = $this->applyOrientation($image, $path, (string) $file->getMimeType());

        if (imagesx($image) > $maxWidth) {
            $image = imagescale($image, $maxWidth) ?: $image;
        }

        imagepalettetotruecolor($image);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, 82);
        $webp = (string) ob_get_clean();

        $target = trim($directory, '/').'/'.Str::random(32).'.webp';
        Storage::disk(self::DISK)->put($target, $webp);

        return $target;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : url(Storage::disk(self::DISK)->url($path));
    }

    /**
     * Re-encodes an image file in place in its own format, dropping all metadata (EXIF, GPS, camera data)
     * after applying the camera's rotation. Files that are not a supported image are left alone.
     */
    public function stripMetadata(string $path, string $mime): void
    {
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            return;
        }

        $image = $this->applyOrientation($image, $path, $mime);

        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, 92),
            'image/png' => imagepng($image, $path, 6),
            default => imagewebp($image, $path, 90),
        };
    }

    private function applyOrientation(\GdImage $image, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }
}
