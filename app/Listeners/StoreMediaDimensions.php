<?php

namespace App\Listeners;

use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/** Records the original width and height so pages can reserve space for images (no layout shift). */
class StoreMediaDimensions
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;
        $size = @getimagesize($media->getPath());

        if ($size === false) {
            return;
        }

        $media->setCustomProperty('width', $size[0])->setCustomProperty('height', $size[1])->saveQuietly();
    }
}
