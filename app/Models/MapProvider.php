<?php

namespace App\Models;

use Database\Factories\MapProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property bool $is_enabled
 * @property string|null $public_key
 * @property string|null $server_key
 * @property array<string, mixed>|null $options
 * @property int|null $daily_limit
 * @property int|null $monthly_limit
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MapProvider extends Model
{
    /** @use HasFactory<MapProviderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'is_enabled',
        'public_key',
        'server_key',
        'options',
        'daily_limit',
        'monthly_limit',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'public_key' => 'encrypted',
            'server_key' => 'encrypted',
            'options' => 'array',
            'daily_limit' => 'integer',
            'monthly_limit' => 'integer',
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
