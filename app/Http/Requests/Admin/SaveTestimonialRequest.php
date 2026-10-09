<?php

namespace App\Http\Requests\Admin;

use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;

class SaveTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('testimonial');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Testimonial::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'position' => ['nullable', 'string', 'max:190'],
            'company' => ['nullable', 'string', 'max:190'],
            'quote' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_photo' => ['boolean'],
            'is_approved' => ['boolean'],
        ];
    }
}
