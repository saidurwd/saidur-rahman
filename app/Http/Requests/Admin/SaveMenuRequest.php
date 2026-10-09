<?php

namespace App\Http\Requests\Admin;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class SaveMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('menu');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Menu::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            'location' => ['required', Rule::in(['header', 'footer'])],
            'url' => ['nullable', 'string', 'max:255', 'regex:/^(\/|#|https?:\/\/|mailto:)/', 'required_without:route'],
            'route' => ['nullable', 'string', 'max:120', 'required_without:url', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value && ! Route::has($value)) {
                    $fail('There is no route with that name.');
                }
            }],
            'is_published' => ['boolean'],
        ];
    }
}
