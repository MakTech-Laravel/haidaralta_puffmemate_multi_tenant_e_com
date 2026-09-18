<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;

/**
 * Single serialization shape for conversation data.
 *
 * The Inertia page, the cursor history endpoint, and the Reverb broadcast all
 * render the same React rows, so they must emit identical message objects.
 */
final class ConversationPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function message(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'type' => $message->type->value,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
            'sender' => self::sender($message),
            'attachments' => $message->attachments
                ->map(fn (MessageAttachment $attachment): array => self::attachment($attachment))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  iterable<int, Message>  $messages
     * @return list<array<string, mixed>>
     */
    public static function messages(iterable $messages): array
    {
        $payload = [];

        foreach ($messages as $message) {
            $payload[] = self::message($message);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function attachment(MessageAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'mime' => $attachment->mime,
            'size' => $attachment->size,
            'is_image' => $attachment->isImage(),
            'url' => route('conversation-attachments.show', $attachment),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function participant(ConversationParticipant $participant): array
    {
        $user = $participant->user;

        return [
            'id' => $participant->id,
            'user_id' => $participant->user_id,
            'name' => $user->name,
            'role' => $participant->role->value,
            'role_label' => $participant->role->label(),
            'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
            'last_read_message_id' => $participant->last_read_message_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function conversation(Conversation $conversation): array
    {
        $order = $conversation->order;

        return [
            'id' => $conversation->id,
            'status' => $conversation->status->value,
            'closed_at' => $conversation->closed_at?->toIso8601String(),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'channel' => $conversation->channelName(),
            'order' => $order === null ? null : [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
            ],
            'branch' => $conversation->branch?->only(['id', 'name', 'slug']),
            'participants' => $conversation->participants
                ->map(fn (ConversationParticipant $participant): array => self::participant($participant))
                ->values()
                ->all(),
        ];
    }

    /**
     * Sender is null on system rows, which render as centered markers.
     *
     * @return array<string, mixed>|null
     */
    private static function sender(Message $message): ?array
    {
        $sender = $message->sender;

        if ($sender === null) {
            return null;
        }

        return [
            'id' => $sender->id,
            'name' => $sender->name,
            'avatar_url' => MediaUrl::resolve($sender->avatar, fallback: null),
        ];
    }
}
