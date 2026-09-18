<?php

namespace App\Services\Conversation;

use App\Enums\ConversationParticipantRole;
use App\Enums\ConversationStatus;
use App\Enums\MessageType;
use App\Enums\OrderStatus;
use App\Events\ConversationClosed;
use App\Events\ConversationInboxUpdated;
use App\Events\ConversationReceiptsUpdated;
use App\Events\MessageCreated;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Support\ConversationUnreadCounts;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lifecycle for order-scoped conversations: open, join, send, read, close.
 */
class ConversationService
{
    public const ATTACHMENT_DISK = 'local';

    /**
     * Open the thread for an order, or return the existing one.
     *
     * Idempotent: `conversations.order_id` is unique, so a repeated call from a
     * second operator returns the same row instead of failing.
     */
    public function openForOrder(Order $order): Conversation
    {
        $existing = Conversation::query()->where('order_id', $order->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($order): Conversation {
            $conversation = Conversation::query()->create([
                'order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'status' => ConversationStatus::Open,
            ]);

            $this->addParticipant($conversation, $order->customer_id, ConversationParticipantRole::Customer);

            if ($order->driver_id !== null) {
                $this->addParticipant($conversation, $order->driver_id, ConversationParticipantRole::Driver);
            }

            $this->touchLastMessage($conversation, $this->writeSystemMessage(
                $conversation,
                'Conversation opened for order '.$order->order_number.'.',
            ));

            return $conversation->refresh();
        });
    }

    /**
     * Join an operator to the thread so their sends and reads are tracked.
     */
    public function joinOperator(Conversation $conversation, User $user): ConversationParticipant
    {
        return $this->addParticipant(
            $conversation,
            $user->id,
            ConversationParticipantRole::fromUserType($user->user_type),
        );
    }

    /**
     * Persist a participant message and broadcast it to the thread.
     *
     * @param  list<UploadedFile>  $attachments
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        ?string $body = null,
        array $attachments = [],
    ): Message {
        if ($conversation->isClosed()) {
            throw new RuntimeException('This conversation is closed.');
        }

        $message = DB::transaction(function () use ($conversation, $sender, $body, $attachments): Message {
            $this->joinOperator($conversation, $sender);

            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'type' => $this->resolveMessageType($attachments),
                'body' => $this->normalizeBody($body),
            ]);

            foreach ($attachments as $file) {
                $this->storeAttachment($conversation, $message, $file);
            }

            $this->touchLastMessage($conversation, $message);

            $this->markRead($conversation, $sender, $message->id);

            return $message;
        });

        $message->load(['sender:id,name,avatar', 'attachments']);

        broadcast(new MessageCreated($message))->toOthers();
        $this->notifyInbox($conversation);

        return $message;
    }

    /**
     * Close the thread when its order reaches a terminal status.
     */
    public function closeForOrder(Order $order, ?string $reason = null): ?Conversation
    {
        $conversation = Conversation::query()->where('order_id', $order->id)->first();

        if ($conversation === null || $conversation->isClosed()) {
            return $conversation;
        }

        return $this->close($conversation, $reason ?? $this->closeReasonFor($order));
    }

    /**
     * Close the thread. Safe to call twice: a second close is a no-op so a
     * double-click cannot stamp the transcript with extra system rows.
     */
    public function close(Conversation $conversation, string $reason): Conversation
    {
        $systemMessage = null;

        DB::transaction(function () use ($conversation, $reason, &$systemMessage): void {
            $locked = Conversation::query()
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->isClosed()) {
                return;
            }

            $locked->forceFill([
                'status' => ConversationStatus::Closed,
                'closed_at' => now(),
            ])->save();

            $systemMessage = $this->writeSystemMessage($locked, $reason);
            $this->touchLastMessage($locked, $systemMessage);
        });

        $conversation->refresh();

        if ($systemMessage instanceof Message) {
            $systemMessage->load(['sender:id,name,avatar', 'attachments']);

            broadcast(new MessageCreated($systemMessage));
            broadcast(new ConversationClosed($conversation));
            $this->notifyInbox($conversation);
        }

        return $conversation;
    }

    /**
     * Move a participant's read marker forward. Never backwards.
     *
     * Reading implies delivery, so the delivered marker is dragged along.
     */
    public function markRead(Conversation $conversation, User $user, ?int $messageId = null): void
    {
        $moved = $this->advanceMarkers($conversation, $user, $messageId, [
            'last_delivered_message_id',
            'last_read_message_id',
        ]);

        if (! $moved) {
            return;
        }

        ConversationUnreadCounts::forget($user->id);

        broadcast(new ConversationReceiptsUpdated($conversation->refresh()));
    }

    /**
     * Acknowledge that a message reached this participant's client.
     *
     * Fired from the thread channel and from the per-user inbox channel, so a
     * recipient who is not looking at the thread still ticks as delivered.
     */
    public function markDelivered(Conversation $conversation, User $user, ?int $messageId = null): void
    {
        if (! $this->advanceMarkers($conversation, $user, $messageId, ['last_delivered_message_id'])) {
            return;
        }

        broadcast(new ConversationReceiptsUpdated($conversation->refresh()));
    }

    /**
     * @param  list<string>  $columns
     * @return bool Whether any marker actually moved.
     */
    private function advanceMarkers(
        Conversation $conversation,
        User $user,
        ?int $messageId,
        array $columns,
    ): bool {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant === null) {
            return false;
        }

        $messageId ??= (int) $conversation->messages()->max('id');

        if ($messageId <= 0) {
            return false;
        }

        $updates = [];

        foreach ($columns as $column) {
            if ($messageId > (int) $participant->{$column}) {
                $updates[$column] = $messageId;
            }
        }

        if ($updates === []) {
            return false;
        }

        $participant->forceFill($updates)->save();

        return true;
    }

    /**
     * Keep the denormalized inbox preview in step with the newest message.
     */
    private function touchLastMessage(Conversation $conversation, Message $message): void
    {
        $conversation->forceFill([
            'last_message_preview' => $this->previewFor($message),
            'last_message_sender_id' => $message->sender_id,
            'last_message_at' => $message->created_at,
        ])->save();
    }

    /**
     * Bust every participant's unread cache and push the inbox row to them.
     */
    private function notifyInbox(Conversation $conversation): void
    {
        $userIds = [];

        foreach ($conversation->participants()->pluck('user_id') as $userId) {
            $userIds[] = (int) $userId;
        }

        if ($userIds === []) {
            return;
        }

        ConversationUnreadCounts::forgetMany($userIds);

        broadcast(new ConversationInboxUpdated($conversation, $userIds));
    }

    /**
     * Attachment-only messages still need a readable inbox line.
     */
    private function previewFor(Message $message): string
    {
        $body = trim((string) $message->body);

        if ($body !== '') {
            return Str::limit($body, 150);
        }

        $count = $message->attachments()->count();

        return match (true) {
            $count > 1 => $count.' attachments',
            $count === 1 => '1 attachment',
            default => '',
        };
    }

    /**
     * Count messages the user has not read yet, ignoring their own.
     */
    public function unreadCountFor(Conversation $conversation, User $user): int
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant === null) {
            return 0;
        }

        return $conversation->messages()
            ->where('id', '>', (int) $participant->last_read_message_id)
            ->where(function ($query) use ($user): void {
                $query->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id);
            })
            ->count();
    }

    public function writeSystemMessage(Conversation $conversation, string $body): Message
    {
        return $conversation->messages()->create([
            'sender_id' => null,
            'type' => MessageType::System,
            'body' => $body,
        ]);
    }

    private function addParticipant(
        Conversation $conversation,
        int $userId,
        ConversationParticipantRole $role,
    ): ConversationParticipant {
        return ConversationParticipant::query()->firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
            ],
            [
                'role' => $role,
                'joined_at' => now(),
            ],
        );
    }

    /**
     * Attachments live on the private disk; downloads go through an authorized route.
     */
    private function storeAttachment(Conversation $conversation, Message $message, UploadedFile $file): void
    {
        $path = $file->store('conversations/'.$conversation->id, self::ATTACHMENT_DISK);

        if ($path === false) {
            throw new RuntimeException('Failed to store the conversation attachment.');
        }

        $message->attachments()->create([
            'disk' => self::ATTACHMENT_DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    private function resolveMessageType(array $attachments): MessageType
    {
        if ($attachments === []) {
            return MessageType::Text;
        }

        $allImages = collect($attachments)->every(
            fn (UploadedFile $file): bool => str_starts_with($file->getClientMimeType(), 'image/'),
        );

        return $allImages ? MessageType::Image : MessageType::File;
    }

    private function normalizeBody(?string $body): ?string
    {
        $body = trim((string) $body);

        return $body === '' ? null : $body;
    }

    private function closeReasonFor(Order $order): string
    {
        return $order->status === OrderStatus::Cancelled
            ? 'Order '.$order->order_number.' was cancelled. This conversation is now closed.'
            : 'Order '.$order->order_number.' was delivered. This conversation is now closed.';
    }
}
