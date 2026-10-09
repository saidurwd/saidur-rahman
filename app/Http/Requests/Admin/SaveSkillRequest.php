<?php

namespace App\Http\Requests\Admin;

use App\Models\Skill;
use Illuminate\Foundation\Http\FormRequest;

class SaveSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('skill');

        return $this->user()->can($record ? 'update' : 'create', $record ?? Skill::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'skill_group_id' => ['required', 'integer', 'exists:skill_groups,id'],
            'name' => ['required', 'string', 'max:190'],
            'level' => ['required', 'integer', 'between:1,5'],
            'years' => ['nullable', 'integer', 'between:0,60'],
            'is_published' => ['boolean'],
        ];
    }
}
