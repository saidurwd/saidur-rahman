<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Shared rules for creating and updating a project. */
abstract class ProjectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $stack = $this->input('tech_stack');

        if (is_string($stack)) {
            $this->merge(['tech_stack' => collect(explode(',', $stack))
                ->map(fn (string $item) => trim($item))
                ->filter()
                ->unique()
                ->values()
                ->all()]);
        }
    }

    abstract protected function currentProject(): ?Project;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $image = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('projects', 'slug')->ignore($this->currentProject())],
            'client' => ['nullable', 'string', 'max:190'],
            'role' => ['nullable', 'string', 'max:190'],
            'period' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:600'],
            'problem' => ['nullable', 'string', 'max:100000'],
            'solution' => ['nullable', 'string', 'max:100000'],
            'architecture' => ['nullable', 'string', 'max:100000'],
            'outcomes' => ['nullable', 'string', 'max:100000'],
            'tech_stack' => ['nullable', 'array', 'max:30'],
            'tech_stack.*' => ['string', 'max:60'],
            'live_url' => ['nullable', 'url:http,https', 'max:255'],
            'repo_url' => ['nullable', 'url:http,https', 'max:255'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'cover' => ['nullable', ...$image],
            'cover_alt' => ['nullable', 'string', 'max:190'],
            'remove_cover' => ['boolean'],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => $image,
            'media_alt' => ['nullable', 'array'],
            'media_alt.*' => ['nullable', 'string', 'max:190'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
        ];
    }
}
