<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a driver's open Orders and Dashboard pages to refresh their stops.
 */
class DriverOrdersChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'driver.orders-changed';

    public const REASON_ASSIGNED = 'assigned';

    public function __construct(
        public int $driverId,
        public string $orderNumber,
        public string $reason = self::REASON_ASSIGNED,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->driverId),
        ];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array{order_number: string, reason: string}
     */
    public function broadcastWith(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'reason' => $this->reason,
        ];
    }
}
