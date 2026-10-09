<?php

namespace App\Models\Concerns;

/** Lets generic admin actions publish/unpublish a model without knowing how it stores that. */
trait HasPublishState
{
    /** @return array<string, mixed> attributes that put the record into the published or unpublished state */
    public function publishState(bool $published): array
    {
        return ['is_published' => $published];
    }
}
