<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SaveSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSettingsRequest;
use App\Models\Setting;
use App\Services\ImageStore;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(SettingService $settings, ImageStore $images): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('admin.settings.edit', [
            'values' => $settings->all(),
            'profilePhotoUrl' => $images->url($settings->get('profile_photo')),
            'ogImageUrl' => $images->url($settings->get('og_image')),
        ]);
    }

    public function update(SaveSettingsRequest $request, SaveSettings $save): RedirectResponse
    {
        $save($request->safe()->all());

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved.');
    }
}
