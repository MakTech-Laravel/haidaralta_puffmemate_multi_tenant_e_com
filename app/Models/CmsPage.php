<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithAppMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property bool $is_published
 * @property array<string, mixed> $payload
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CmsPage extends Model implements HasMedia
{
    use InteractsWithAppMedia;

    public const HERO_COLLECTION = 'hero';

    public const MEDIA_COLLECTION = 'media';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'is_published',
        'payload',
        'updated_by',
    ];

    public function registerMediaCollections(): void
    {
        $this->registerSingleFileCollection(self::HERO_COLLECTION);
        $this->registerSingleFileCollection(self::MEDIA_COLLECTION);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
