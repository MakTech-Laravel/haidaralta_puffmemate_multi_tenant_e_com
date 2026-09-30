<?php

namespace App\Models;

use App\Enums\DriverStockAssignmentMode;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverStockAssignmentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int|null $assigned_by
 * @property DriverStockAssignmentMode $mode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch|null $branch
 * @property-read User|null $assignedBy
 * @property-read Collection<int, User> $drivers
 * @property-read Collection<int, DriverStockAssignmentItem> $items
 */
class DriverStockAssignment extends Model
{
    /** @use HasFactory<DriverStockAssignmentFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'assigned_by',
        'mode',
    ];

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
     * @return BelongsToMany<User, $this>
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'driver_stock_assignment_driver',
            'driver_stock_assignment_id',
            'user_id',
        );
    }

    /**
     * @return HasMany<DriverStockAssignmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DriverStockAssignmentItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => DriverStockAssignmentMode::class,
        ];
    }
}
