<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class MediaUploadService
{
    private const RASTER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif'];

    private const SVG_EXTENSIONS = ['svg'];

    private const SVG_MIMES = ['image/svg+xml', 'image/svg'];

    /**
     * Replace or clear a single-file collection.
     *
     * A new upload always clears the collection first so the previous media
     * row and disk files are deleted (file wins over remove).
     */
    public function syncSingle(
        HasMedia $model,
        string $collection,
        ?UploadedFile $file = null,
        bool $remove = false,
    ): ?Media {
        if ($file !== null) {
            $model->clearMediaCollection($collection);

            return $this->attachUploadedFile($model, $collection, $file);
        }

        if ($remove) {
            $model->clearMediaCollection($collection);

            return null;
        }

        return $model->getFirstMedia($collection);
    }

    /**
     * Append uploaded files to a multi-file collection.
     *
     * @param  list<UploadedFile>  $files
     * @return list<Media>
     */
    public function syncMany(HasMedia $model, string $collection, array $files): array
    {
        $attached = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $attached[] = $this->attachUploadedFile($model, $collection, $file);
        }

        return $attached;
    }

    /**
     * @param  list<int>  $mediaIds
     */
    public function deleteFromCollection(HasMedia $model, string $collection, array $mediaIds): void
    {
        if ($mediaIds === []) {
            return;
        }

        $model->getMedia($collection)
            ->whereIn('id', $mediaIds)
            ->each(fn (Media $media) => $media->delete());
    }

    public function clear(HasMedia $model, string $collection): void
    {
        $model->clearMediaCollection($collection);
    }

    /**
     * Attach an existing absolute or public-disk file into a collection (seeders / legacy import).
     */
    public function attachFromPath(
        HasMedia $model,
        string $collection,
        string $absolutePath,
        bool $preservingOriginal = true,
        ?string $fileName = null,
    ): ?Media {
        if (! is_file($absolutePath)) {
            return null;
        }

        $adder = $model->addMedia($absolutePath);

        if ($preservingOriginal) {
            $adder->preservingOriginal();
        }

        if ($fileName !== null && $fileName !== '') {
            $adder->usingFileName($fileName);
        }

        return $adder->toMediaCollection($collection);
    }

    public function attachUploadedFile(HasMedia $model, string $collection, UploadedFile $file): Media
    {
        if ($this->isSvg($file)) {
            return $model
                ->addMedia($file)
                ->usingFileName($this->safeFileName($file, 'svg'))
                ->toMediaCollection($collection);
        }

        $prepared = $this->prepareRasterAsWebp($file);

        try {
            $media = $model
                ->addMedia($prepared['path'])
                ->usingFileName($prepared['file_name'])
                ->toMediaCollection($collection);
        } finally {
            if (($prepared['temporary'] ?? false) && is_file($prepared['path'])) {
                @unlink($prepared['path']);
            }
        }

        return $media;
    }

    /**
     * @return array{path: string, file_name: string, temporary: bool}
     */
    private function prepareRasterAsWebp(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $sourcePath = $file->getRealPath() ?: $file->getPathname();

        if ($extension === 'webp') {
            $this->optimizeQuietly($sourcePath);

            return [
                'path' => $sourcePath,
                'file_name' => $this->safeFileName($file, 'webp'),
                'temporary' => false,
            ];
        }

        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::uuid()->toString().'.webp';

        Image::load($sourcePath)
            ->format('webp')
            ->save($tempPath);

        $this->optimizeQuietly($tempPath);

        return [
            'path' => $tempPath,
            'file_name' => $this->safeFileName($file, 'webp'),
            'temporary' => true,
        ];
    }

    private function isSvg(UploadedFile $file): bool
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, self::SVG_EXTENSIONS, true)) {
            return true;
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        return in_array($mime, self::SVG_MIMES, true);
    }

    private function safeFileName(UploadedFile $file, string $extension): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $base = Str::slug($base);

        if ($base === '') {
            $base = 'upload';
        }

        return $base.'.'.$extension;
    }

    private function optimizeQuietly(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        try {
            OptimizerChainFactory::create()->optimize($path);
        } catch (Throwable) {
            // Optimizer binaries may be missing in local/CI — conversion still succeeds.
        }
    }

    public static function isRasterExtension(?string $extension): bool
    {
        return in_array(strtolower((string) $extension), self::RASTER_EXTENSIONS, true);
    }

    public static function isSvgExtension(?string $extension): bool
    {
        return in_array(strtolower((string) $extension), self::SVG_EXTENSIONS, true);
    }
}
