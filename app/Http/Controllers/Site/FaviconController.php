<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ImageStore;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/** Browser tab and home-screen icons cut from the profile photo; without a photo the SVG monogram is used. */
class FaviconController extends Controller
{
    public function favicon(SettingService $settings): Response|RedirectResponse
    {
        return $this->icon($settings, 64, true);
    }

    public function appleTouchIcon(SettingService $settings): Response|RedirectResponse
    {
        return $this->icon($settings, 180, false);
    }

    private function icon(SettingService $settings, int $size, bool $round): Response|RedirectResponse
    {
        $path = $settings->get('profile_photo');
        $disk = Storage::disk(ImageStore::DISK);

        if (! $path || str_starts_with($path, 'http') || ! $disk->exists($path)) {
            return redirect('/favicon.svg');
        }

        $source = @imagecreatefromstring((string) $disk->get($path));

        if ($source === false) {
            return redirect('/favicon.svg');
        }

        // Square crop from the top of the photo, where the face is in a portrait.
        $side = min(imagesx($source), imagesy($source));
        $x = intdiv(imagesx($source) - $side, 2);
        $y = (int) (($side < imagesy($source)) ? (imagesy($source) - $side) * 0.15 : 0);

        $icon = imagecreatetruecolor($size, $size);
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        imagefill($icon, 0, 0, (int) imagecolorallocatealpha($icon, 0, 0, 0, 127));
        imagecopyresampled($icon, $source, 0, 0, $x, $y, $size, $size, $side, $side);

        if ($round) {
            $this->roundCorners($icon, $size);
        }

        ob_start();
        imagepng($icon);
        $png = (string) ob_get_clean();

        return response($png)->header('Content-Type', 'image/png')->header('Cache-Control', 'public, max-age=86400');
    }

    /** Makes every pixel outside the inscribed circle transparent. */
    private function roundCorners(\GdImage $icon, int $size): void
    {
        $radius = $size / 2;

        for ($px = 0; $px < $size; $px++) {
            for ($py = 0; $py < $size; $py++) {
                if (hypot($px + 0.5 - $radius, $py + 0.5 - $radius) > $radius) {
                    imagesetpixel($icon, $px, $py, (int) imagecolorallocatealpha($icon, 0, 0, 0, 127));
                }
            }
        }
    }
}
