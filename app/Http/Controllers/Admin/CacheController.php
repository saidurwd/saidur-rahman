<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;

class CacheController extends Controller
{
    public function clear(CacheService $cache, SettingService $settings): RedirectResponse
    {
        $cache->flush();
        $settings->forget();

        return back()->with('status', 'Site cache cleared.');
    }
}
