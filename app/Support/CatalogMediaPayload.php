<?php

namespace App\Support;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class CatalogMediaPayload
{
    /**
     * File name of the first media item in a single-file collection (display label for edit forms).
     */
    public static function fileName(HasMedia $model, string $collection = 'image'): ?string
    {
        return $model->getFirstMedia($collection)?->file_name;
    }

    /**
     * Gallery rows keyed by media id, ordered by the media library order column.
     *
     * @return list<array{id: int, path: string, url: string, sort_order: int, mime_type: string}>
     */
    public static function gallery(HasMedia $model, string $collection = 'gallery'): array
    {
        return $model->getMedia($collection)
            ->map(fn (Media $media): array => [
                'id' => (int) $media->id,
                'path' => $media->file_name,
                'url' => $media->getUrl(),
                'sort_order' => (int) $media->order_column,
                'mime_type' => $media->mime_type ?: 'image/jpeg',
            ])
            ->values()
            ->all();
    }
}
