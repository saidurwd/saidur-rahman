<?php

namespace App\Http\Requests\Admin;

use App\Models\Project;

class UpdateProjectRequest extends ProjectRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    protected function currentProject(): ?Project
    {
        /** @var Project */
        return $this->route('project');
    }
}
