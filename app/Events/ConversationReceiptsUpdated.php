<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Moves everyone's delivery and read ticks.
 *
 * Receipt state is not stored per message: the payload carries each
 * participant's two markers and the client derives a message's tick by
 * comparing its id against them. That keeps this event O(participants)
 * instead of O(messages).
 */
class ConversationReceiptsUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'conversation.receipts-updated';

    public function __construct(public Conversation $conversation) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(MessageCreated::channelFor($this->conversation->id)),
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
        return [
            'conversation_id' => $this->conversation->id,
            'receipts' => self::receiptsFor($this->conversation),
        ];
    }

    /**
     * @return list<array{user_id: int, last_delivered_message_id: int, last_read_message_id: int}>
     */
    public static function receiptsFor(Conversation $conversation): array
    {
        $receipts = [];

        foreach ($conversation->participants()->get() as $participant) {
            /** @var ConversationParticipant $participant */
            $receipts[] = [
                'user_id' => (int) $participant->user_id,
                'last_delivered_message_id' => (int) $participant->last_delivered_message_id,
                'last_read_message_id' => (int) $participant->last_read_message_id,
            ];
        }

        return $receipts;
    }
}
