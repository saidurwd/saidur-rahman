<?php

namespace App\Http\Requests\Admin;

use App\Models\Experience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('experience');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Experience::class);
    }

    protected function prepareForValidation(): void
    {
        $lines = $this->input('achievements');

        $this->merge([
            'achievements' => is_string($lines)
                ? collect(preg_split('/\R/', $lines) ?: [])->map(fn (string $line) => trim($line))->filter()->values()->all()
                : $lines,
            'end_date' => $this->boolean('is_current') ? null : $this->input('end_date'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:190'],
            'role' => ['required', 'string', 'max:190'],
            'location' => ['nullable', 'string', 'max:190'],
            'employment_type' => ['nullable', Rule::in(['Full-time', 'Part-time', 'Contract', 'Consulting', 'Freelance'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'achievements' => ['nullable', 'array', 'max:30'],
            'achievements.*' => ['string', 'max:500'],
            'is_published' => ['boolean'],
        ];
    }
}
