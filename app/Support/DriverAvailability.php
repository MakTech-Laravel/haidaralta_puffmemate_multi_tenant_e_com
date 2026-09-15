<?php

namespace App\Support;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverRosterStatus;
use App\Models\DriverRoster;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DriverAvailability
{
    /**
     * Effective status for display:
     * - stored on_break / busy overrides win
     * - else scheduled roster covering "now" → available
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

        if ($driver->relationLoaded('driverRosters')) {
            $onDuty = self::coversMoment($driver->driverRosters, $moment);
        } else {
            $onDuty = DriverRoster::query()
                ->where('driver_id', $driver->id)
                ->where('status', DriverRosterStatus::Scheduled->value)
                ->whereDate('start_date', '<=', $moment->toDateString())
                ->whereDate('end_date', '>=', $moment->toDateString())
                ->get()
                ->contains(fn (DriverRoster $roster): bool => $roster->coversMoment($moment));
        }

        return $onDuty
            ? DriverAvailabilityStatus::Available
            : DriverAvailabilityStatus::Offline;
    }

    /**
     * @param  Collection<int, DriverRoster>|iterable<DriverRoster>  $rosters
     */
    public static function coversMoment(iterable $rosters, CarbonInterface $moment): bool
    {
        foreach ($rosters as $roster) {
            if ($roster->coversMoment($moment)) {
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
