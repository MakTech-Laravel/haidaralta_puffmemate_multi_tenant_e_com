<?php

namespace App\Models;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property string|null $vehicle_image_path
 * @property-read User|null $user
 */
class DriverProfile extends Model
{
    /** @use HasFactory<DriverProfileFactory> */
    use AuditsChanges, HasFactory;

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
        'vehicle_image_path',
    ];

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
