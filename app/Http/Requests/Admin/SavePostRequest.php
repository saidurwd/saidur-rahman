<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SavePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('post');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Post::class);
    }

    /** Tags arrive as one comma-separated string and are cleaned into a list of unique names. */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags');

        if (is_string($tags)) {
            $this->merge(['tags' => collect(explode(',', $tags))
                ->map(fn (string $tag) => Str::squish($tag))
                ->filter()
                ->unique(fn (string $tag) => Str::lower($tag))
                ->values()
                ->all()]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'excerpt' => ['nullable', 'string', 'max:600'],
            'body' => ['nullable', 'string', 'max:300000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date', Rule::requiredIf($this->input('status') === PostStatus::Scheduled->value)],
            'tags' => ['nullable', 'array', 'max:15'],
            'tags.*' => ['string', 'max:40'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_alt' => ['nullable', 'string', 'max:190'],
            'remove_cover' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['published_at.required' => 'Choose when this article should go live.'];
    }
}
