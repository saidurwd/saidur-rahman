<?php

namespace App\Http\Requests\Admin;

use App\Models\SkillGroup;
use Illuminate\Foundation\Http\FormRequest;

class SaveSkillGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('skill_group');

        return $this->user()->can($record ? 'update' : 'create', $record ?? SkillGroup::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'is_published' => ['boolean'],
        ];
    }
}
