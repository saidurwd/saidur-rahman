<?php

namespace App\Observers;

use App\Services\SettingService;

class SettingObserver
{
    public function __construct(private SettingService $settings) {}

    public function saved(): void
    {
        $this->settings->forget();
    }

    public function deleted(): void
    {
        $this->settings->forget();
    }
}
