<?php

namespace App\Actions\Admin;

use App\Models\CvFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveCvFile
{
    /**
     * Stores a CV version. The file goes on the private disk under a random name, so the
     * visitor-facing name never reveals the stored path. Only one version per label is active.
     *
     * @param  array{label: string, file?: UploadedFile|null, is_active?: bool}  $data
     */
    public function __invoke(CvFile $cv, array $data): CvFile
    {
        return DB::transaction(function () use ($cv, $data) {
            $oldPath = $cv->exists ? $cv->file_path : null;

            $cv->label = $data['label'];
            $cv->is_active = (bool) ($data['is_active'] ?? false);

            if (($data['file'] ?? null) instanceof UploadedFile) {
                $cv->file_path = $data['file']->storeAs('cv', Str::random(40).'.pdf', CvFile::DISK);
            }

            $cv->save();

            if ($oldPath && $oldPath !== $cv->file_path) {
                Storage::disk(CvFile::DISK)->delete($oldPath);
            }

            if ($cv->is_active) {
                $this->deactivateOthers($cv);
            }

            return $cv;
        });
    }

    public function activate(CvFile $cv): void
    {
        DB::transaction(function () use ($cv) {
            $cv->update(['is_active' => true]);
            $this->deactivateOthers($cv);
        });
    }

    private function deactivateOthers(CvFile $cv): void
    {
        CvFile::query()->where('label', $cv->label)->whereKeyNot($cv->id)->where('is_active', true)
            ->get()->each->update(['is_active' => false]);
    }
}
