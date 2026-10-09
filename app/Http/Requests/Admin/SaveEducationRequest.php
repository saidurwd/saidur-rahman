<?php

namespace App\Http\Requests\Admin;

use App\Models\Education;
use Illuminate\Foundation\Http\FormRequest;

class SaveEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('education');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Education::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:190'],
            'degree' => ['required', 'string', 'max:190'],
            'field' => ['nullable', 'string', 'max:190'],
            'start_year' => ['nullable', 'integer', 'between:1950,2100'],
            'end_year' => ['nullable', 'integer', 'between:1950,2100', 'gte:start_year'],
            'is_published' => ['boolean'],
        ];
    }
}
