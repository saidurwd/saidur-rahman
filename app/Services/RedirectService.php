<?php

namespace App\Services;

use App\Models\Redirect;

class RedirectService
{
    /**
     * Records that $from now lives at $to. Existing redirects that pointed at $from are
     * re-pointed so visitors never go through a chain, and a redirect back to $from is dropped.
     */
    public function record(string $from, string $to, int $code = 301): void
    {
        if ($from === $to) {
            return;
        }

        Redirect::query()->where('to_path', $from)->update(['to_path' => $to]);
        Redirect::query()->where('from_path', $to)->delete();
        Redirect::query()->updateOrCreate(['from_path' => $from], ['to_path' => $to, 'code' => $code]);
        Redirect::query()->where('from_path', $to)->delete();
    }

    public function resolve(string $path): ?Redirect
    {
        $path = '/'.trim($path, '/');

        return Redirect::query()->where('from_path', $path)->first();
    }
}
