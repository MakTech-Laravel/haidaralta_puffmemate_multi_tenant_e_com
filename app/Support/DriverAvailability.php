<?php

namespace App\Support;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DriverAvailability
{
    /**
     * Effective status for display:
     * - stored on_break / busy overrides win
     * - else a scheduled shift assignment covering "now" → available
     * - else offline
     */
    public static function effective(User $driver, ?CarbonInterface $at = null): DriverAvailabilityStatus
    {
        $driver->loadMissing('driverProfile');

        $stored = $driver->driverProfile?->availability_status;

        if ($stored === DriverAvailabilityStatus::OnBreak || $stored === DriverAvailabilityStatus::Busy) {
            return $stored;
        }

        $moment = $at?->copy() ?? now();

        if ($driver->relationLoaded('shiftAssignments')) {
            $assignments = $driver->shiftAssignments;
        } else {
            $assignments = UserShiftAssignment::query()
                ->with('shiftTemplate')
                ->where('user_id', $driver->id)
                ->where('status', ShiftAssignmentStatus::Scheduled->value)
                ->whereDate('start_date', '<=', $moment->toDateString())
                ->whereDate('end_date', '>=', $moment->toDateString())
                ->get();
        }

        return self::coversMoment($assignments, $moment)
            ? DriverAvailabilityStatus::Available
            : DriverAvailabilityStatus::Offline;
    }

    /**
     * @param  Collection<int, UserShiftAssignment>|iterable<UserShiftAssignment>  $assignments
     */
    public static function coversMoment(iterable $assignments, CarbonInterface $moment): bool
    {
        foreach ($assignments as $assignment) {
            if ($assignment instanceof UserShiftAssignment && $assignment->coversMoment($moment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{value: string, label: string}
     */
    public static function effectivePayload(User $driver, ?CarbonInterface $at = null): array
    {
        $status = self::effective($driver, $at);

        return [
            'value' => $status->value,
            'label' => $status->label(),
        ];
    }
}
