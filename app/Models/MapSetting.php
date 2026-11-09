<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $display_provider
 * @property string $geocoding_provider
 * @property string $routing_provider
 * @property string $fallback_provider
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MapSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'display_provider',
        'geocoding_provider',
        'routing_provider',
        'fallback_provider',
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
