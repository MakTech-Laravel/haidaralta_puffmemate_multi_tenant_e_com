<?php

namespace App\Services\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Events\DriverLocationUpdated;
use App\Models\DriverBranchAssignment;
use App\Models\DriverLocation;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Support\DriverAvailability;
use App\Support\Geo\Distance;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Receives a driver's GPS readings during work time.
 *
 * Cost and load rules: the latest position lives only in the cache, viewers are notified
 * over Reverb only when the driver has really moved (or as a slow heartbeat), and the
 * database receives sampled breadcrumbs only while a delivery is active.
 */
class DriverLocationService
{
    public const CACHE_PREFIX = 'driver.location.';

    public function __construct(private readonly DriverMapWatchers $watchers) {}

    /**
     * @param  array{latitude: float, longitude: float, accuracy?: float|null, speed?: float|null, heading?: int|null, recorded_at: CarbonInterface}  $reading
     */
    public function record(User $driver, array $reading): DriverLocationOutcome
    {
        if (! DriverAvailability::shouldTrack($driver)) {
            return DriverLocationOutcome::notTracking();
        }

        $config = (array) config('drivers.tracking');
        $recordedAt = $reading['recorded_at'];
        $timestamp = $recordedAt->getTimestamp();

        if (isset($reading['accuracy']) && $reading['accuracy'] > $config['max_accuracy_meters']) {
            return DriverLocationOutcome::ignored('inaccurate');
        }

        if ($timestamp < now()->getTimestamp() - $config['max_reading_age_seconds']) {
            return DriverLocationOutcome::ignored('too_old');
        }

        /** @var array<string, mixed>|null $previous */
        $previous = Cache::get(self::cacheKey($driver->id));

        if ($previous !== null) {
            if ($timestamp <= $previous['ts']) {
                return DriverLocationOutcome::ignored('out_of_order');
            }

            if ($this->impliedSpeedKmh($previous, $reading, $timestamp) > $config['max_speed_kmh']) {
                return DriverLocationOutcome::ignored('jump');
            }
        }

        $entry = [
            'lat' => $reading['latitude'],
            'lng' => $reading['longitude'],
            'accuracy' => $reading['accuracy'] ?? null,
            'speed' => $reading['speed'] ?? null,
            'heading' => $reading['heading'] ?? null,
            'ts' => $timestamp,
            'bc_lat' => $previous['bc_lat'] ?? null,
            'bc_lng' => $previous['bc_lng'] ?? null,
            'bc_ts' => $previous['bc_ts'] ?? null,
            'crumb_lat' => $previous['crumb_lat'] ?? null,
            'crumb_lng' => $previous['crumb_lng'] ?? null,
            'crumb_ts' => $previous['crumb_ts'] ?? null,
            'fulfillment_id' => $previous['fulfillment_id'] ?? null,
            'branch_ids' => $previous['branch_ids'] ?? [],
        ];

        $shouldBroadcast = $this->movedEnough($entry, 'bc', $config['min_broadcast_distance_meters'], $config['broadcast_heartbeat_seconds']);
        $shouldCrumb = $this->movedEnough($entry, 'crumb', $config['breadcrumb_min_distance_meters'], $config['breadcrumb_heartbeat_seconds']);

        if ($shouldBroadcast || $shouldCrumb) {
            $entry['fulfillment_id'] = $this->activeFulfillmentId($driver);
            $entry['branch_ids'] = $this->activeBranchIds($driver);
        }

        if ($shouldBroadcast && config('drivers.tracking.broadcast_only_when_watched') && ! $this->watchers->isWatched($entry['branch_ids'])) {
            // Nobody has a live map open, so skip the Reverb push. The cached position still serves
            // any map that opens later, and the unmoved broadcast mark makes the next reading send.
            $shouldBroadcast = false;
        }

        if ($shouldCrumb) {
            if ($entry['fulfillment_id'] !== null) {
                $this->writeBreadcrumb($driver, $entry, $recordedAt);
            }

            // Marked even without a delivery so idle drivers do not trigger the lookups every ping.
            [$entry['crumb_lat'], $entry['crumb_lng'], $entry['crumb_ts']] = [$entry['lat'], $entry['lng'], $timestamp];
        }

        if ($shouldBroadcast) {
            [$entry['bc_lat'], $entry['bc_lng'], $entry['bc_ts']] = [$entry['lat'], $entry['lng'], $timestamp];

            DriverLocationUpdated::dispatch(
                $driver->id,
                $entry['branch_ids'],
                $entry['lat'],
                $entry['lng'],
                $timestamp,
                $entry['heading'],
                $entry['speed'],
                $entry['accuracy'],
                $entry['fulfillment_id'],
            );
        }

        Cache::put(self::cacheKey($driver->id), $entry, (int) $config['last_position_ttl_seconds']);

        return new DriverLocationOutcome(tracking: true, accepted: true, broadcast: $shouldBroadcast);
    }

    /**
     * Last known position of a driver, or null when none was received recently.
     *
     * @return array{lat: float, lng: float, accuracy: float|null, speed: float|null, heading: int|null, ts: int, fulfillment_id: int|null, branch_ids: list<int>}|null
     */
    public function lastPosition(int $driverId): ?array
    {
        $entry = Cache::get(self::cacheKey($driverId));

        if (! is_array($entry)) {
            return null;
        }

        return [
            'lat' => $entry['lat'],
            'lng' => $entry['lng'],
            'accuracy' => $entry['accuracy'],
            'speed' => $entry['speed'],
            'heading' => $entry['heading'],
            'ts' => $entry['ts'],
            'fulfillment_id' => $entry['fulfillment_id'],
            'branch_ids' => $entry['branch_ids'],
        ];
    }

    public function forget(int $driverId): void
    {
        Cache::forget(self::cacheKey($driverId));
    }

    public static function cacheKey(int $driverId): string
    {
        return self::CACHE_PREFIX.$driverId;
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $reading
     */
    private function impliedSpeedKmh(array $previous, array $reading, int $timestamp): float
    {
        $seconds = $timestamp - $previous['ts'];

        if ($seconds <= 0) {
            return 0.0;
        }

        $meters = Distance::meters($previous['lat'], $previous['lng'], $reading['latitude'], $reading['longitude']);

        return ($meters / $seconds) * 3.6;
    }

    /**
     * True when there is no earlier mark, the driver moved far enough since it, or the heartbeat is due.
     *
     * @param  array<string, mixed>  $entry
     */
    private function movedEnough(array $entry, string $mark, int $minMeters, int $heartbeatSeconds): bool
    {
        if ($entry["{$mark}_ts"] === null) {
            return true;
        }

        if ($heartbeatSeconds <= $entry['ts'] - $entry["{$mark}_ts"]) {
            return true;
        }

        return Distance::meters($entry["{$mark}_lat"], $entry["{$mark}_lng"], $entry['lat'], $entry['lng']) >= $minMeters;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function writeBreadcrumb(User $driver, array $entry, CarbonInterface $recordedAt): void
    {
        DriverLocation::query()->create([
            'driver_id' => $driver->id,
            'fulfillment_id' => $entry['fulfillment_id'],
            'latitude' => $entry['lat'],
            'longitude' => $entry['lng'],
            'accuracy' => $entry['accuracy'],
            'speed' => $entry['speed'],
            'heading' => $entry['heading'],
            'recorded_at' => $recordedAt,
        ]);
    }

    private function activeFulfillmentId(User $driver): ?int
    {
        $id = OrderFulfillment::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', FulfillmentStatus::driverWorkload())
            ->latest('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return list<int>
     */
    private function activeBranchIds(User $driver): array
    {
        return DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->pluck('branch_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
