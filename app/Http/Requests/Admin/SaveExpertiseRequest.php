<?php

namespace App\Http\Requests\Admin;

use App\Models\Expertise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveExpertiseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('expertise');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Expertise::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('expertises', 'slug')->ignore($this->route('expertise'))],
            'icon' => ['nullable', 'string', 'max:60'],
            'summary' => ['nullable', 'string', 'max:600'],
            'body' => ['nullable', 'string', 'max:100000'],
            'is_published' => ['boolean'],
        ];
    }
}
