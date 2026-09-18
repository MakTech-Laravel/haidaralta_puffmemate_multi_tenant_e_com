<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Nudges a participant's inbox list when one of their threads changes.
 *
 * Fired per user so the left-hand list can reorder and update its unread badge
 * without the thread being open.
 */
class ConversationInboxUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'conversation.inbox-updated';

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public Conversation $conversation,
        public array $userIds,
    ) {}

    public static function channelFor(int $userId): string
    {
        return 'conversation-inbox.'.$userId;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (int $userId): PrivateChannel => new PrivateChannel(self::channelFor($userId)),
            $this->userIds,
        );
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
        return [
            'conversation_id' => $this->conversation->id,
            // Lets a recipient who is not in the thread still ack delivery,
            // which is what separates one tick from two.
            'last_message_id' => (int) $this->conversation->messages()->max('id'),
            'status' => $this->conversation->status->value,
            'last_message_preview' => $this->conversation->last_message_preview,
            'last_message_at' => $this->conversation->last_message_at?->toIso8601String(),
        ];
    }
}
