<?php

namespace App\Actions\Admin;

use App\Contracts\HasPublicPath;
use App\Services\HtmlSanitizer;
use App\Services\RedirectService;
use Illuminate\Database\Eloquent\Model;

class SaveSlugged
{
    public function __construct(private HtmlSanitizer $sanitizer, private RedirectService $redirects) {}

    /**
     * Saves a model that has a slug and a publicPath(): purifies the given rich and plain text
     * fields, keeps the slug stable, and writes a 301 when the slug changes.
     *
     * @template TModel of Model&HasPublicPath
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     * @param  list<string>  $rich
     * @param  list<string>  $plain
     * @return TModel
     */
    public function __invoke(Model&HasPublicPath $model, array $data, array $rich = [], array $plain = []): Model
    {
        $oldSlug = $model->exists ? $model->getAttribute('slug') : null;

        foreach ($rich as $field) {
            $data[$field] = $this->sanitizer->clean($data[$field] ?? null);
        }
        foreach ($plain as $field) {
            $data[$field] = $this->sanitizer->plain($data[$field] ?? null);
        }

        if (blank($data['slug'] ?? null)) {
            unset($data['slug']);
        }

        $model->fill($data)->save();

        if ($oldSlug !== null && $oldSlug !== $model->getAttribute('slug')) {
            $this->redirects->record($model->publicPath($oldSlug), $model->publicPath());
        }

        return $model;
    }
}
