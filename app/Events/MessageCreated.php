<?php

namespace App\Events;

use App\Models\Message;
use App\Support\ConversationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fans a new chat message out to everyone watching the order thread.
 */
class MessageCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'conversation.message-created';

    public function __construct(public Message $message) {}

    public static function channelFor(int $conversationId): string
    {
        return 'conversation.'.$conversationId;
    }

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(self::channelFor($this->message->conversation_id)),
        ];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender:id,name,avatar', 'attachments']);

        return [
            'message' => ConversationPayload::message($this->message),
        ];
    }
}
