<?php

namespace App\Http\Requests\Admin;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageBulkRequest extends FormRequest
{
    public const ACTIONS = ['read', 'unread', 'star', 'unstar', 'spam', 'not-spam', 'delete'];

    public function authorize(): bool
    {
        return $this->user()->can('update', Message::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(self::ACTIONS)],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['ids.required' => 'Select at least one message first.'];
    }
}
