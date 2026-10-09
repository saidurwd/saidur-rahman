<?php

namespace App\Actions\Admin;

use App\Services\ImageStore;
use App\Services\SettingService;
use Illuminate\Http\UploadedFile;

class SaveSettings
{
    /** key => group */
    public const TEXT = [
        'site_name' => 'profile', 'headline' => 'profile', 'positioning' => 'profile', 'hero_title' => 'profile',
        'bio' => 'profile', 'location' => 'profile', 'contact_email' => 'profile', 'availability_text' => 'profile',
        'linkedin' => 'social', 'github' => 'social',
        'seo_title' => 'seo', 'seo_description' => 'seo',
    ];

    public const BOOLEANS = ['availability_open' => 'profile'];

    /** key => [group, max width] */
    public const IMAGES = ['profile_photo' => ['profile', 900], 'og_image' => ['seo', 1200]];

    public function __construct(private SettingService $settings, private ImageStore $images) {}

    /** @param array<string, mixed> $data validated request data */
    public function __invoke(array $data): void
    {
        foreach (self::TEXT as $key => $group) {
            $this->settings->set($key, filled($data[$key] ?? null) ? trim((string) $data[$key]) : null, $group);
        }

        foreach (self::BOOLEANS as $key => $group) {
            $this->settings->set($key, (bool) ($data[$key] ?? false), $group);
        }

        foreach (self::IMAGES as $key => [$group, $width]) {
            $upload = $data[$key] ?? null;
            $remove = ! empty($data["remove_{$key}"]);

            if (! $remove && ! $upload instanceof UploadedFile) {
                continue;
            }

            $this->images->delete($this->settings->get($key));
            $this->settings->set($key, $upload instanceof UploadedFile && ! $remove ? $this->images->store($upload, "settings/{$key}", $width) : null, $group);
        }
    }
}
