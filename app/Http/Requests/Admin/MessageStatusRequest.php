<?php

namespace App\Http\Requests\Admin;

use App\Enums\MessageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('message'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(MessageStatus::class)]];
    }
}
