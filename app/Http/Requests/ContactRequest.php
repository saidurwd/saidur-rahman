<?php

namespace App\Http\Requests;

use App\Services\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Single-line fields lose line breaks and control characters, so they can never inject mail headers. */
    protected function prepareForValidation(): void
    {
        $clean = fn (?string $value) => Str::of((string) $value)->replaceMatches('/[\x00-\x1F\x7F]+/u', ' ')->squish()->toString();

        $this->merge([
            'name' => $clean($this->input('name')),
            'email' => Str::lower($clean($this->input('email'))),
            'company' => $clean($this->input('company')),
            'subject' => $clean($this->input('subject')),
            'message' => trim(str_replace("\r\n", "\n", (string) $this->input('message'))),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Bots fill every field they find. People never see this one.
            'website' => ['nullable', 'string', 'max:255'],
            // Encrypted timestamp from when the form was rendered, to spot instant submissions.
            'form_token' => ['required', 'string'],
            'cf-turnstile-response' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'message.min' => 'Please write a little more so I know how to help.',
            'form_token.required' => 'This form has expired. Please reload the page and try again.',
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! app(Turnstile::class)->verify($this->input('cf-turnstile-response'), $this->ip())) {
                $validator->errors()->add('cf-turnstile-response', 'We could not confirm you are human. Please try again.');
            }
        }];
    }
}
