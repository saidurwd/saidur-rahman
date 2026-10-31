<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Turns what an admin typed into a clean list of unique names. */
class TagList
{
    /**
     * Names are separated by commas or by a # that starts a word ("ERP, Laravel" or "#erp #laravel"). A # inside a
     * word is kept, so "C#" stays "C#".
     *
     * @return list<string>
     */
    public static function split(string $input): array
    {
        return collect(preg_split('/,|(?<!\S)#/', $input) ?: [])
            ->map(fn (string $name) => Str::squish($name))
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->values()
            ->all();
    }
}
