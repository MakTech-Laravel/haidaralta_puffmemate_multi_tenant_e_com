<?php

namespace App\Models;

use App\Enums\DriverAssignmentStatus;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverBranchAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $driver_id
 * @property int $branch_id
 * @property DriverAssignmentStatus $status
 * @property int|null $assigned_by
 * @property-read User|null $driver
 * @property-read Branch|null $branch
 * @property-read User|null $assignedBy
 */
class DriverBranchAssignment extends Model
{
    /** @use HasFactory<DriverBranchAssignmentFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'branch_id',
        'status',
        'assigned_by',
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
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DriverAssignmentStatus::class,
        ];
    }
}
