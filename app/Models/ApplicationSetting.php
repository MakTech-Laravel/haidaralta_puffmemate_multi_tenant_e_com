<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithAppMedia;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property string $app_name
 * @property string $app_short_name
 * @property string|null $short_description
 * @property string $theme_color
 * @property string $background_color
 * @property string $date_format
 * @property string $time_format
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApplicationSetting extends Model implements HasMedia
{
    use InteractsWithAppMedia;

    public const LOGO_COLLECTION = 'logo';

    public const FAVICON_COLLECTION = 'favicon';

    public const PWA_192_COLLECTION = 'pwa_192';

    public const PWA_512_COLLECTION = 'pwa_512';

    public const PWA_MASKABLE_COLLECTION = 'pwa_maskable';

    public const APPLE_TOUCH_COLLECTION = 'apple_touch';

    /**
     * @var list<string>
     */
    public const MEDIA_COLLECTIONS = [
        self::LOGO_COLLECTION,
        self::FAVICON_COLLECTION,
        self::PWA_192_COLLECTION,
        self::PWA_512_COLLECTION,
        self::PWA_MASKABLE_COLLECTION,
        self::APPLE_TOUCH_COLLECTION,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'app_name',
        'app_short_name',
        'short_description',
        'theme_color',
        'background_color',
        'date_format',
        'time_format',
        'updated_by',
    ];

    public function registerMediaCollections(): void
    {
        foreach (self::MEDIA_COLLECTIONS as $collection) {
            $this->registerSingleFileCollection($collection);
        }
    }

    public function mediaCollectionUrl(string $collection, ?string $fallback = null): ?string
    {
        return MediaUrl::fromCollection($this, $collection, $fallback);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
