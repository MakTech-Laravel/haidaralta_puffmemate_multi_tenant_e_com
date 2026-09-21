<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $app_name
 * @property string $app_short_name
 * @property string|null $short_description
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property string|null $pwa_icon_192_path
 * @property string|null $pwa_icon_512_path
 * @property string|null $pwa_maskable_path
 * @property string|null $apple_touch_icon_path
 * @property string $theme_color
 * @property string $background_color
 * @property string $display_timezone
 * @property string $date_format
 * @property string $time_format
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApplicationSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'app_name',
        'app_short_name',
        'short_description',
        'logo_path',
        'favicon_path',
        'pwa_icon_192_path',
        'pwa_icon_512_path',
        'pwa_maskable_path',
        'apple_touch_icon_path',
        'theme_color',
        'background_color',
        'display_timezone',
        'date_format',
        'time_format',
        'updated_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
