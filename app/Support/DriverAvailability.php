<?php

namespace App\Support;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Models\UserShiftDay;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DriverAvailability
{
    /**
     * Effective status for display:
     * - stored on_break / busy overrides win
     * - else a scheduled shift day covering "now" → available
     * - else offline
     */
    public static function effective(User $driver, ?CarbonInterface $at = null): DriverAvailabilityStatus
    {
        $driver->loadMissing('driverProfile');

        $stored = $driver->driverProfile?->availability_status;

        if ($stored === DriverAvailabilityStatus::OnBreak || $stored === DriverAvailabilityStatus::Busy) {
            return $stored;
        }

        return self::onShift($driver, $at)
            ? DriverAvailabilityStatus::Available
            : DriverAvailabilityStatus::Offline;
    }

    /**
     * Whether live location should be collected right now: the driver is not on a break and is
     * either inside a scheduled shift window or still carrying an active delivery. Stored
     * busy/offline never switch tracking off, because a busy driver is exactly who is on the road.
     */
    public static function shouldTrack(User $driver, ?CarbonInterface $at = null): bool
    {
        $driver->loadMissing('driverProfile');

        if ($driver->driverProfile?->availability_status === DriverAvailabilityStatus::OnBreak) {
            return false;
        }

        return self::onShift($driver, $at) || self::hasActiveDelivery($driver);
    }

    public static function hasActiveDelivery(User $driver): bool
    {
        return OrderFulfillment::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', FulfillmentStatus::driverWorkload())
            ->exists();
    }

    /**
     * Whether a scheduled/override shift day covers the moment, ignoring break/busy overrides.
     */
    public static function onShift(User $driver, ?CarbonInterface $at = null): bool
    {
        $moment = DateTimeFormat::at($at);

        if ($driver->relationLoaded('shiftDays')) {
            $days = $driver->shiftDays;
        } elseif ($driver->relationLoaded('shiftAssignments')) {
            $days = $driver->shiftAssignments
                ->filter(fn ($assignment): bool => $assignment->status === ShiftAssignmentStatus::Scheduled)
                ->flatMap(fn ($assignment) => $assignment->relationLoaded('days')
                    ? $assignment->days
                    : $assignment->days()->with('shiftTemplate')->onDuty()->whereDate('duty_on', $moment->toDateString())->get());
        } else {
            $days = UserShiftDay::query()
                ->with('shiftTemplate')
                ->where('user_id', $driver->id)
                ->onDuty()
                ->whereDate('duty_on', $moment->toDateString())
                ->whereHas('assignment', fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
                ->get();
        }

        return self::coversMoment($days, $moment);
    }

    /**
     * @param  Collection<int, UserShiftDay>|iterable<mixed>  $days
     */
    public static function coversMoment(iterable $days, CarbonInterface $moment): bool
    {
        foreach ($days as $day) {
            if ($day instanceof UserShiftDay && $day->coversMoment($moment)) {
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
