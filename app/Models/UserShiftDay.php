<?php

namespace App\Models;

use App\Enums\ShiftDayKind;
use App\Models\Concerns\AuditsChanges;
use App\Support\DutyWindow;
use Carbon\CarbonInterface;
use Database\Factories\UserShiftDayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_shift_assignment_id
 * @property int $user_id
 * @property int $branch_id
 * @property Carbon $duty_on
 * @property int|null $shift_template_id
 * @property ShiftDayKind $kind
 */
class UserShiftDay extends Model
{
    /** @use HasFactory<UserShiftDayFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_shift_assignment_id',
        'user_id',
        'branch_id',
        'duty_on',
        'shift_template_id',
        'kind',
    ];

    public function coversMoment(CarbonInterface $moment): bool
    {
        if (! $this->kind->isOnDuty()) {
            return false;
        }

        $template = $this->shiftTemplate;

        if ($template === null) {
            return false;
        }

        if ($this->duty_on->toDateString() !== $moment->toDateString()) {
            return false;
        }

        return DutyWindow::coversTime(
            $moment->format('H:i:s'),
            $template->startTimeValue(),
            $template->endTimeValue(),
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOnDuty(Builder $query): Builder
    {
        return $query->whereIn('kind', [
            ShiftDayKind::Scheduled,
            ShiftDayKind::Override,
        ])->whereNotNull('shift_template_id');
    }

    /**
     * @return BelongsTo<UserShiftAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(UserShiftAssignment::class, 'user_shift_assignment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duty_on' => 'date',
            'kind' => ShiftDayKind::class,
        ];
    }
}
