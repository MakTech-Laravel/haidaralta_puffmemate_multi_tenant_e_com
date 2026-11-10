<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A driver's live position for the branch and platform live maps.
 *
 * Sent without waiting on a transaction or queue so positions stay real-time; `ts` lets
 * clients drop an update that arrives after a newer one.
 */
class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public const BROADCAST_AS = 'driver.location-updated';

    public const PLATFORM_CHANNEL = 'platform.drivers.locations';

    /**
     * @param  list<int>  $branchIds  Active branch assignments of the driver
     */
    public function __construct(
        public int $driverId,
        public array $branchIds,
        public float $latitude,
        public float $longitude,
        public int $timestamp,
        public ?int $heading = null,
        public ?float $speed = null,
        public ?float $accuracy = null,
        public ?int $fulfillmentId = null,
        public bool $coarse = false,
    ) {}

    public static function branchChannel(int $branchId): string
    {
        return "branch.{$branchId}.drivers";
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(self::PLATFORM_CHANNEL),
            ...array_map(
                fn (int $branchId): PrivateChannel => new PrivateChannel(self::branchChannel($branchId)),
                $this->branchIds,
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array{driver_id: int, branch_ids: list<int>, lat: float, lng: float, ts: int, heading: int|null, speed: float|null, accuracy: float|null, fulfillment_id: int|null, coarse: bool}
     */
    public function broadcastWith(): array
    {
        return [
            'driver_id' => $this->driverId,
            'branch_ids' => $this->branchIds,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'ts' => $this->timestamp,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'accuracy' => $this->accuracy,
            'fulfillment_id' => $this->fulfillmentId,
            'coarse' => $this->coarse,
        ];
    }
}
