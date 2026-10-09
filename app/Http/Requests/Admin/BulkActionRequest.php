<?php

namespace App\Http\Requests\Admin;

use App\Actions\ApplyBulkAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(ApplyBulkAction::ACTIONS)],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'Select at least one item first.'];
    }
}
