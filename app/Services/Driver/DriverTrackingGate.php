<?php

namespace App\Services\Driver;

use App\Models\User;
use App\Support\DriverAvailability;

/**
 * Decides whether a driver must be sharing location right now and builds the browser tracker's
 * settings. Shared by the portal middleware, the gate page and the Inertia shared props.
 */
class DriverTrackingGate
{
    public function __construct(private readonly DriverLocationService $locations) {}

    public function isEnabled(): bool
    {
        return (bool) config('drivers.tracking.gate_enabled');
    }

    /**
     * The driver is on duty (or mid-delivery) and so should be sharing location.
     */
    public function shouldShare(User $driver): bool
    {
        return DriverAvailability::shouldTrack($driver);
    }

    public function hasFreshPosition(User $driver): bool
    {
        $position = $this->locations->lastPosition($driver->id);

        return $position !== null
            && $position['ts'] >= now()->getTimestamp() - (int) config('drivers.tracking.stale_after_seconds');
    }

    /**
     * True when the portal must be blocked until the driver allows location again.
     */
    public function isBlocked(User $driver): bool
    {
        return $this->isEnabled()
            && $this->shouldShare($driver)
            && ! $this->hasFreshPosition($driver);
    }

    /**
     * @return array{should_share: bool, location_url: string, gate_url: string, heartbeat_seconds: int, min_send_interval_seconds: int, min_distance_meters: int, recheck_seconds: int}
     */
    public function payload(User $driver): array
    {
        $client = (array) config('drivers.tracking.client');

        return [
            'should_share' => $this->shouldShare($driver),
            'location_url' => route('driver.location.store', absolute: false),
            'gate_url' => route('driver.location.required', absolute: false),
            'heartbeat_seconds' => (int) $client['heartbeat_seconds'],
            'min_send_interval_seconds' => (int) $client['min_send_interval_seconds'],
            'min_distance_meters' => (int) $client['min_distance_meters'],
            'recheck_seconds' => (int) $client['recheck_seconds'],
        ];
    }
}
