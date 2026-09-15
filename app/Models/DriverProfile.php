<?php

namespace App\Models;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }
}
