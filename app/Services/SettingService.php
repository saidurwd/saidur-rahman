<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public const CACHE_KEY = 'settings.all';

    /** @var array<string, mixed>|null per-request copy, so repeated get() calls do not hit the cache store */
    private ?array $loaded = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->loaded ??= Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->pluck('value', 'key')
            ->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    /** @return array<string, mixed> */
    public function group(string $group): array
    {
        return Setting::query()->where('group', $group)->pluck('value', 'key')->all();
    }

    public function forget(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }
}
