<?php

namespace App\Models;

use App\Enums\ShiftAssignmentStatus;
use App\Models\Concerns\AuditsChanges;
use App\Support\DutyWindow;
use Carbon\CarbonInterface;
use Database\Factories\UserShiftAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $branch_id
 * @property int $shift_template_id
 * @property int $shift_roster_period_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property array<mixed>|null $days_of_week
 * @property ShiftAssignmentStatus $status
 * @property int|null $assigned_by
 * @property-read ShiftTemplate|null $shiftTemplate
 * @property-read ShiftRosterPeriod|null $shiftRosterPeriod
 */
class UserShiftAssignment extends Model
{
    /** @use HasFactory<UserShiftAssignmentFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'shift_template_id',
        'shift_roster_period_id',
        'start_date',
        'end_date',
        'days_of_week',
        'status',
        'assigned_by',
    ];

    /**
     * @return list<int>
     */
    public function normalizedDaysOfWeek(): array
    {
        /** @var list<mixed>|null $days */
        $days = $this->days_of_week;

        return DriverRoster::normalizeDays(is_array($days) ? $days : null);
    }

    public function coversMoment(CarbonInterface $moment): bool
    {
        if ($this->status !== ShiftAssignmentStatus::Scheduled) {
            return false;
        }

        $template = $this->shiftTemplate;

        if ($template === null) {
            return false;
        }

        return DutyWindow::coversMoment(
            $moment,
            $this->start_date->format('Y-m-d'),
            $this->end_date->format('Y-m-d'),
            $template->startTimeValue(),
            $template->endTimeValue(),
            $this->normalizedDaysOfWeek(),
        );
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
     * @return BelongsTo<ShiftRosterPeriod, $this>
     */
    public function shiftRosterPeriod(): BelongsTo
    {
        return $this->belongsTo(ShiftRosterPeriod::class);
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
            'start_date' => 'date',
            'end_date' => 'date',
            'days_of_week' => 'array',
            'status' => ShiftAssignmentStatus::class,
        ];
    }
}
