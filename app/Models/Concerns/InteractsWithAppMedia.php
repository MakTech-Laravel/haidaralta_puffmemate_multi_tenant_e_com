<?php

namespace App\Models\Concerns;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;

trait InteractsWithAppMedia
{
    use InteractsWithMedia;

    /**
     * @param  list<string>  $mimeTypes
     */
    protected function registerSingleFileCollection(string $name, array $mimeTypes = []): MediaCollection
    {
        $collection = $this->addMediaCollection($name)->singleFile();

        if ($mimeTypes !== []) {
            $collection->acceptsMimeTypes($mimeTypes);
        }

        return $collection;
    }

    /**
     * @param  list<string>  $mimeTypes
     */
    protected function registerMultiFileCollection(string $name, array $mimeTypes = []): MediaCollection
    {
        $collection = $this->addMediaCollection($name);

        if ($mimeTypes !== []) {
            $collection->acceptsMimeTypes($mimeTypes);
        }

        return $collection;
    }

    public function mediaUrlFor(string $collection, string $conversion = ''): ?string
    {
        $url = $this->getFirstMediaUrl($collection, $conversion);

        return $url !== '' ? $url : null;
    }

    /**
     * @return list<string>
     */
    public function mediaUrlsFor(string $collection, string $conversion = ''): array
    {
        return $this->getMedia($collection)
            ->map(fn ($media): string => $media->getUrl($conversion))
            ->filter(fn (string $url): bool => $url !== '')
            ->values()
            ->all();
    }
}
