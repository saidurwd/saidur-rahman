<?php

namespace App\Actions\Admin;

use App\Models\Project;
use App\Services\HtmlSanitizer;
use App\Services\RedirectService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SaveProject
{
    private const RICH_FIELDS = ['problem', 'solution', 'architecture', 'outcomes'];

    public function __construct(private HtmlSanitizer $sanitizer, private RedirectService $redirects) {}

    /**
     * Creates or updates a project from validated form data: purifies rich text, keeps the slug
     * stable (and writes a 301 when it changes), and applies cover/gallery changes.
     *
     * @param  array<string, mixed>  $data  validated request data
     */
    public function __invoke(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $oldSlug = $project->exists ? $project->slug : null;

            foreach (self::RICH_FIELDS as $field) {
                $data[$field] = $this->sanitizer->clean($data[$field] ?? null);
            }
            $data['summary'] = $this->sanitizer->plain($data['summary'] ?? null);
            $data['tech_stack'] = array_values($data['tech_stack'] ?? []);
            $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

            if (blank($data['slug'] ?? null)) {
                unset($data['slug']);
            }

            if (! $project->exists) {
                $data['sort_order'] = (int) Project::withTrashed()->max('sort_order') + 1;
            }

            $project->fill($data)->save();

            if ($oldSlug !== null && $oldSlug !== $project->slug) {
                $this->redirects->record($project->publicPath($oldSlug), $project->publicPath());
            }

            $this->syncMedia($project, $data);

            return $project;
        });
    }

    /** @param array<string, mixed> $data */
    private function syncMedia(Project $project, array $data): void
    {
        if (! empty($data['remove_cover'])) {
            $project->clearMediaCollection('cover');
        }

        if (($data['cover'] ?? null) instanceof UploadedFile) {
            $project->addMedia($data['cover'])
                ->withCustomProperties(['alt' => $data['cover_alt'] ?? ''])
                ->toMediaCollection('cover');
        } elseif ($cover = $project->getFirstMedia('cover')) {
            $cover->setCustomProperty('alt', $data['cover_alt'] ?? '')->save();
        }

        foreach ($data['remove_media'] ?? [] as $id) {
            $project->getMedia('gallery')->firstWhere('id', (int) $id)?->delete();
        }

        foreach ($data['media_alt'] ?? [] as $id => $alt) {
            $project->getMedia('gallery')->firstWhere('id', (int) $id)?->setCustomProperty('alt', (string) $alt)->save();
        }

        foreach ($data['gallery'] ?? [] as $file) {
            $project->addMedia($file)->toMediaCollection('gallery');
        }
    }
}
