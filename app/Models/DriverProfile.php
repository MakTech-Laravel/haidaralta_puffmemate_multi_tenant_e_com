<?php

namespace App\Models;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\InteractsWithAppMedia;
use App\Support\MediaUrl;
use Database\Factories\DriverProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $license_number
 * @property DriverVehicleType $vehicle_type
 * @property DriverAvailabilityStatus $availability_status
 * @property string|null $notes
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property-read User|null $user
 */
class DriverProfile extends Model implements HasMedia
{
    /** @use HasFactory<DriverProfileFactory> */
    use AuditsChanges, HasFactory, InteractsWithAppMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'license_number',
        'vehicle_type',
        'availability_status',
        'notes',
        'address',
        'latitude',
        'longitude',
    ];

    public function registerMediaCollections(): void
    {
        $this->registerSingleFileCollection('vehicle');
    }

    public function vehicleImageUrl(?string $fallback = null): ?string
    {
        return MediaUrl::fromCollection($this, 'vehicle', $fallback);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_type' => DriverVehicleType::class,
            'availability_status' => DriverAvailabilityStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
