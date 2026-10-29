<?php

namespace App\Support;

use App\Enums\ConversationType;
use App\Events\ConversationReceiptsUpdated;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;

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
    public static function message(Message $message, bool $includeSenderRole = true): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'type' => $message->type->value,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
            'sender' => self::sender($message, $includeSenderRole),
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
    public static function messages(iterable $messages, bool $includeSenderRole = true): array
    {
        $payload = [];

        foreach ($messages as $message) {
            $payload[] = self::message($message, $includeSenderRole);
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
            'role_label' => self::staffRoleLabel($user, $participant),
            'avatar_url' => $user->avatarUrl(),
        ];
    }

    /**
     * Left-hand inbox row. Unread is viewer-specific, so callers overlay it.
     *
     * @return array<string, mixed>
     */
    public static function inboxRow(Conversation $conversation, ?User $viewer = null): array
    {
        $conversation->loadMissing([
            'order:id,order_number,status',
            'branch:id,name,slug',
            'driver:id,name',
            'driver.media',
            'participants.user:id,name',
            'participants.user.media',
        ]);

        $title = match (true) {
            $conversation->type === ConversationType::DriverOps && $viewer?->isDriver() => $conversation->branch?->name ?? 'Branch',
            $conversation->type === ConversationType::DriverOps => $conversation->driver?->name ?? 'Driver',
            $conversation->type === ConversationType::PlatformBranch && $viewer?->isBranch() => 'Platform',
            $conversation->type === ConversationType::PlatformBranch => $conversation->branch?->name ?? 'Branch',
            default => $conversation->order?->order_number ?? 'Order',
        };

        return [
            'id' => $conversation->id,
            'type' => $conversation->type->value,
            'title' => $title,
            'order_number' => $conversation->order?->order_number,
            'order_status_label' => $conversation->order?->status->label(),
            'status' => $conversation->status->value,
            'branch' => $conversation->branch?->only(['id', 'name', 'slug']),
            'driver' => $conversation->driver === null ? null : [
                'id' => $conversation->driver->id,
                'name' => $conversation->driver->name,
                'avatar_url' => $conversation->driver->avatarUrl(),
            ],
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count' => 0,
            'participants' => $conversation->participants
                ->map(fn (ConversationParticipant $participant): array => [
                    'user_id' => $participant->user_id,
                    'name' => $participant->user->name,
                    'role_label' => self::staffRoleLabel($participant->user, $participant),
                    'avatar_url' => $participant->user->avatarUrl(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function conversation(Conversation $conversation): array
    {
        $conversation->loadMissing([
            'order:id,order_number,status',
            'branch:id,name,slug',
            'driver:id,name',
            'driver.media',
            'participants.user:id,name',
            'participants.user.media',
            'participants.user.roles',
        ]);

        $order = $conversation->order;

        return [
            'id' => $conversation->id,
            'type' => $conversation->type->value,
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
            'driver' => $conversation->driver === null ? null : [
                'id' => $conversation->driver->id,
                'name' => $conversation->driver->name,
                'avatar_url' => $conversation->driver->avatarUrl(),
            ],
            'branch' => $conversation->branch?->only(['id', 'name', 'slug']),
            'participants' => $conversation->participants
                ->map(fn (ConversationParticipant $participant): array => self::participant($participant))
                ->values()
                ->all(),
            'receipts' => ConversationReceiptsUpdated::receiptsFor($conversation),
        ];
    }

    /**
     * Branch/platform responses include role_label; driver responses omit it.
     *
     * @return array<string, mixed>|null
     */
    private static function sender(Message $message, bool $includeSenderRole = true): ?array
    {
        $sender = $message->sender;

        if ($sender === null) {
            return null;
        }

        $payload = [
            'id' => $sender->id,
            'name' => $sender->name,
            'avatar_url' => $sender->avatarUrl(),
        ];

        if (! $includeSenderRole) {
            return $payload;
        }

        $message->loadMissing(['conversation.participants.user.roles', 'sender.roles']);

        $participant = $message->conversation?->participants
            ->firstWhere('user_id', $sender->id);

        $payload['role_label'] = $participant instanceof ConversationParticipant
            ? self::staffRoleLabel($sender, $participant)
            : self::fallbackRoleLabel($sender);

        return $payload;
    }

    private static function staffRoleLabel(User $user, ConversationParticipant $participant): string
    {
        if ($user->relationLoaded('roles') || method_exists($user, 'roles')) {
            $user->loadMissing('roles');
            $roleName = $user->roles->first()?->name;

            if (is_string($roleName) && $roleName !== '') {
                return $roleName;
            }
        }

        return $participant->role->label();
    }

    private static function fallbackRoleLabel(User $user): string
    {
        $user->loadMissing('roles');

        return $user->roles->first()?->name
            ?? ($user->isDriver() ? 'Driver' : 'Staff');
    }
}
