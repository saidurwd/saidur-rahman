<?php

namespace App\Http\Requests\Admin;

use App\Models\CvFile;
use Illuminate\Foundation\Http\FormRequest;

class SaveCvFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('cv_file');

        return $this->user()->can($record ? 'update' : 'create', $record ?? CvFile::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            // A file is required for a new version; on edit it is optional and replaces the old one.
            'file' => [$this->route('cv_file') ? 'nullable' : 'required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
            'is_active' => ['boolean'],
        ];
    }
}
