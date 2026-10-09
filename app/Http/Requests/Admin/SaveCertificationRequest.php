<?php

namespace App\Http\Requests\Admin;

use App\Models\Certification;
use Illuminate\Foundation\Http\FormRequest;

class SaveCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('certification');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Certification::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'issuer' => ['required', 'string', 'max:190'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'credential_url' => ['nullable', 'url:http,https', 'max:255'],
            'is_published' => ['boolean'],
        ];
    }
}
