<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithAppMedia;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $sort_order
 * @property bool $is_active
 * @property string $eyebrow
 * @property string $title
 * @property string $description
 * @property string $primary_cta_label
 * @property string $primary_cta_href
 * @property string $secondary_cta_label
 * @property string $secondary_cta_href
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HomeBanner extends Model implements HasMedia
{
    use InteractsWithAppMedia;

    public const IMAGE_COLLECTION = 'image';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sort_order',
        'is_active',
        'eyebrow',
        'title',
        'description',
        'primary_cta_label',
        'primary_cta_href',
        'secondary_cta_label',
        'secondary_cta_href',
    ];

    public function registerMediaCollections(): void
    {
        $this->registerSingleFileCollection(self::IMAGE_COLLECTION);
    }

    public function imageUrl(?string $fallback = null): ?string
    {
        return MediaUrl::fromCollection($this, self::IMAGE_COLLECTION, $fallback);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
