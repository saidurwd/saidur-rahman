<?php

namespace App\Listeners;

use App\Services\ImageStore;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/**
 * Runs on every image added through the media library: removes metadata such as GPS from the original,
 * then records its width and height so pages can reserve space for it (no layout shift).
 */
class StoreMediaDimensions
{
    public function __construct(private ImageStore $images) {}

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;
        $path = $media->getPath();

        $this->images->stripMetadata($path, (string) $media->mime_type);
        clearstatcache(true, $path);

        $size = @getimagesize($path);

        if ($size === false) {
            return;
        }

        $media->size = (int) filesize($path);
        $media->setCustomProperty('width', $size[0])->setCustomProperty('height', $size[1])->saveQuietly();
    }
}
