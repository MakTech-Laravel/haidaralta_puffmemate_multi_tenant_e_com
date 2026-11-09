<?php

namespace App\Models;

use Database\Factories\DriverLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A sampled point on a driver's path during an active delivery.
 *
 * @property int $id
 * @property int $driver_id users.id
 * @property int|null $fulfillment_id
 * @property string $latitude
 * @property string $longitude
 * @property float|null $accuracy
 * @property float|null $speed
 * @property int|null $heading
 * @property Carbon $recorded_at
 */
class DriverLocation extends Model
{
    /** @use HasFactory<DriverLocationFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'fulfillment_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'recorded_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return BelongsTo<OrderFulfillment, $this>
     */
    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'float',
            'speed' => 'float',
            'heading' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }
}
