<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use App\Support\ConversationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Nudges an operator's inbox when a thread they can see changes.
 *
 * Fired per user so the left-hand list can insert, reorder, and re-badge
 * without the thread being open. The optional message is the belt-and-suspenders
 * path for an already-open transcript when the presence channel is not joined.
 */
class ConversationInboxUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const BROADCAST_AS = 'conversation.inbox-updated';

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public Conversation $conversation,
        public array $userIds,
        public ?Message $message = null,
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
        if ($this->message !== null) {
            $this->message->loadMissing(['sender:id,name,avatar', 'attachments']);
        }

        return [
            'conversation_id' => $this->conversation->id,
            // Lets a recipient who is not in the thread still ack delivery,
            // which is what separates one tick from two.
            'last_message_id' => $this->message?->id ?? (int) $this->conversation->messages()->max('id'),
            'sender_id' => $this->conversation->last_message_sender_id,
            'status' => $this->conversation->status->value,
            'last_message_preview' => $this->conversation->last_message_preview,
            'last_message_at' => $this->conversation->last_message_at?->toIso8601String(),
            'row' => ConversationPayload::inboxRow($this->conversation),
            'message' => $this->message !== null
                ? ConversationPayload::message($this->message)
                : null,
        ];
    }
}
