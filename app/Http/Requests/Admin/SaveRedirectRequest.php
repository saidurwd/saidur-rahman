<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('redirect');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Redirect::class);
    }

    protected function prepareForValidation(): void
    {
        $from = trim((string) strtok(trim((string) $this->input('from_path')), '?#'));
        $from = $from === '' ? '' : '/'.trim($from, '/');
        $to = trim((string) $this->input('to_path'));

        $this->merge([
            'from_path' => $from,
            'to_path' => preg_match('#^[a-z][a-z0-9+.-]*:#i', $to) || $to === '' ? $to : '/'.ltrim($to, '/'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from_path' => ['required', 'string', 'max:255', 'not_in:/', 'regex:#^/[^\s]*$#', 'not_regex:#^/admin(/|$)#', Rule::unique('redirects', 'from_path')->ignore($this->route('redirect'))],
            'to_path' => ['required', 'string', 'max:255', 'different:from_path', 'regex:#^(/|https?://)[^\s]*$#'],
            'code' => ['required', Rule::in([301, 302, 307, 308])],
        ];
    }
}
