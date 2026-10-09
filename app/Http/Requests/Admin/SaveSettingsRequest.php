<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $image = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        return [
            'site_name' => ['required', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:190'],
            'positioning' => ['nullable', 'string', 'max:500'],
            'hero_title' => ['nullable', 'string', 'max:190'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'location' => ['nullable', 'string', 'max:190'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'availability_open' => ['boolean'],
            'availability_text' => ['nullable', 'string', 'max:190'],
            'linkedin' => ['nullable', 'url:https', 'max:255'],
            'github' => ['nullable', 'url:https', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'profile_photo' => $image,
            'remove_profile_photo' => ['boolean'],
            'og_image' => $image,
            'remove_og_image' => ['boolean'],
        ];
    }
}
