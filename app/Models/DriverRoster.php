<?php

namespace App\Models;

use App\Enums\DriverRosterStatus;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverRosterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverRoster extends Model
{
    /** @use HasFactory<DriverRosterFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'branch_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'status' => DriverRosterStatus::class,
        ];
    }
}
