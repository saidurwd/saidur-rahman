<?php

namespace App\Http\Requests\Admin;

use App\Models\Project;

class StoreProjectRequest extends ProjectRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    protected function currentProject(): ?Project
    {
        return null;
    }
}
