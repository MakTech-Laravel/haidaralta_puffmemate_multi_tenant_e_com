<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Public diagnostic event. Broadcast immediately (no queue) on a public channel
 * so the platform realtime console can verify Backend → Reverb → Browser without auth.
 */
class ReverbPingEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $message,
        public readonly string $triggeredAt,
        public readonly string $source = 'platform',
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('reverb-ping'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ping';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'triggered_at' => $this->triggeredAt,
            'source' => $this->source,
        ];
    }
}
