<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePageRequest extends FormRequest
{
    /** Paths that belong to the app itself and must not be taken by a page. */
    private const RESERVED_SLUGS = [
        'admin', 'about', 'experience', 'projects', 'blog', 'services', 'media', 'contact', 'cv',
        'sitemap.xml', 'feed', 'robots.txt', 'styleguide', 'up', 'login', 'storage', 'build', 'favicon.svg',
    ];

    public function authorize(): bool
    {
        $record = $this->route('page');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Page::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(self::RESERVED_SLUGS), Rule::unique('pages', 'slug')->ignore($this->route('page'))],
            'body' => ['nullable', 'string', 'max:200000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'is_published' => ['boolean'],
        ];
    }
}
