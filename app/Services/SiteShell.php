<?php

namespace App\Services;

/** The data every public view shares (name, menus, socials, availability), computed once per request. */
class SiteShell
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    private ?int $forRequest = null;

    public function __construct(private SettingService $settings, private NavigationService $navigation) {}

    /** @return array<string, mixed> */
    public function data(): array
    {
        // Recompute when a new request starts (matters for tests and long-running workers).
        $request = spl_object_id(request());
        if ($this->forRequest !== $request) {
            $this->data = null;
            $this->forRequest = $request;
        }

        return $this->data ??= [
            'siteName' => $this->settings->get('site_name', config('app.name')),
            'headerMenu' => $this->navigation->items('header'),
            'footerMenu' => $this->navigation->items('footer'),
            'socialLinks' => array_filter([
                'LinkedIn' => $this->settings->get('linkedin'),
                'GitHub' => $this->settings->get('github'),
            ]),
            'contactEmail' => $this->settings->get('contact_email'),
            'availability' => $this->settings->get('availability_open') ? $this->settings->get('availability_text') : null,
        ];
    }
}
