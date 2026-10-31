<?php

namespace App\Http\Requests\Admin;

use App\Models\Video;
use App\Support\YouTube;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('video');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Video::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('videos', 'slug')->ignore($this->route('video'))],
            'youtube_url' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (YouTube::id((string) $value) === null) {
                    $fail('Paste a YouTube link, for example https://www.youtube.com/watch?v=… or https://youtu.be/….');
                }
            }],
            'description' => ['nullable', 'string', 'max:5000'],
            'topic' => ['nullable', 'string', 'max:60'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'youtube_url' => 'YouTube link',
            'published_at' => 'publish date',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
        ];
    }
}
