<?php

namespace App\Actions\Admin;

use App\Models\Video;
use App\Support\YouTube;

class SaveVideo
{
    public function __construct(private SaveSlugged $saveSlugged) {}

    /**
     * Saves a video from the form: the pasted link becomes the stored YouTube ID, the rest goes through
     * SaveSlugged (plain-text clean-up, stable slug, 301 when the slug changes).
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Video $video, array $data): Video
    {
        $data['youtube_id'] = YouTube::id((string) $data['youtube_url']);
        unset($data['youtube_url']);

        /** @var Video */
        return ($this->saveSlugged)($video, $data, [], ['description', 'topic']);
    }
}
