<?php

namespace App\Models;

use App\Enums\ShiftOwnerType;
use App\Models\Concerns\AuditsChanges;
use App\Support\DutyWindow;
use Database\Factories\ShiftTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ShiftOwnerType $owner_type
 * @property int|null $branch_id
 * @property string $name
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property bool $is_overnight
 * @property bool $is_active
 * @property int|null $created_by
 */
class ShiftTemplate extends Model
{
    /** @use HasFactory<ShiftTemplateFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_type',
        'branch_id',
        'name',
        'start_time',
        'end_time',
        'is_overnight',
        'is_active',
        'created_by',
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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<UserShiftAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(UserShiftAssignment::class);
    }

    public function startTimeValue(): string
    {
        return $this->start_time->format('H:i:s');
    }

    public function endTimeValue(): string
    {
        return $this->end_time->format('H:i:s');
    }

    public function isPlatformOwned(): bool
    {
        return $this->owner_type === ShiftOwnerType::Platform && $this->branch_id === null;
    }

    public function isBranchOwned(): bool
    {
        return $this->owner_type === ShiftOwnerType::Branch && $this->branch_id !== null;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePlatformOwned(Builder $query): Builder
    {
        return $query->where('owner_type', ShiftOwnerType::Platform)->whereNull('branch_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBranchOwned(Builder $query): Builder
    {
        return $query->where('owner_type', ShiftOwnerType::Branch)->whereNotNull('branch_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailableToBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $scope) use ($branchId): void {
                $scope->where(function (Builder $platform): void {
                    $platform->where('owner_type', ShiftOwnerType::Platform)
                        ->whereNull('branch_id');
                })->orWhere(function (Builder $branch) use ($branchId): void {
                    $branch->where('owner_type', ShiftOwnerType::Branch)
                        ->where('branch_id', $branchId);
                });
            });
    }

    public static function overnightFromTimes(string $startTime, string $endTime): bool
    {
        return DutyWindow::isOvernight($startTime, $endTime);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_type' => ShiftOwnerType::class,
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
