<?php

namespace Database\Seeders;

use App\Enums\ConversationParticipantRole;
use App\Enums\MessageType;
use App\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Hand-written order threads so the inbox has realistic content to look at.
 *
 * Every line is written out rather than faked: the previews, the unread badges
 * and the thread grouping only look right when the copy reads like a real
 * delivery conversation.
 */
class OrderConversationSeeder extends Seeder
{
    /**
     * @return list<array{
     *     order_number: string,
     *     branch_email: string,
     *     order_status: OrderStatus,
     *     with_driver: bool,
     *     messages: list<array{from: string, body: string, minutes_ago: int}>
     * }>
     */
    private function threads(): array
    {
        return [
            [
                'order_number' => 'PMM-2026-1041',
                'branch_email' => UserSeeder::BRANCH_1_ADMIN,
                'order_status' => OrderStatus::OutForDelivery,
                'with_driver' => true,
                'messages' => [
                    ['from' => 'customer', 'body' => 'Hi, I ordered the mango ice 20mg but the confirmation email says blueberry. Can you check?', 'minutes_ago' => 190],
                    ['from' => 'branch', 'body' => 'Checking the pick list now, give me a moment.', 'minutes_ago' => 186],
                    ['from' => 'branch', 'body' => 'You are right, the wrong SKU was scanned. I have swapped it to mango ice 20mg and the driver has the correct box.', 'minutes_ago' => 181],
                    ['from' => 'customer', 'body' => 'Perfect, thank you for sorting that out so quickly.', 'minutes_ago' => 177],
                    ['from' => 'driver', 'body' => 'On my way, about 15 minutes out. I will call when I am downstairs.', 'minutes_ago' => 24],
                ],
            ],
            [
                'order_number' => 'PMM-2026-1042',
                'branch_email' => UserSeeder::BRANCH_2_ADMIN,
                'order_status' => OrderStatus::Confirmed,
                'with_driver' => false,
                'messages' => [
                    ['from' => 'customer', 'body' => 'Can I add a second pod pack to this order before it ships?', 'minutes_ago' => 95],
                    ['from' => 'branch', 'body' => 'The order has not been packed yet, so yes. Which flavour would you like?', 'minutes_ago' => 88],
                    ['from' => 'customer', 'body' => 'Watermelon, same strength as the first one please.', 'minutes_ago' => 84],
                    ['from' => 'branch', 'body' => 'Added. The difference will show on the updated invoice, no extra delivery charge.', 'minutes_ago' => 80],
                ],
            ],
            [
                'order_number' => 'PMM-2026-1043',
                'branch_email' => UserSeeder::BRANCH_1_ADMIN,
                'order_status' => OrderStatus::Pending,
                'with_driver' => false,
                'messages' => [
                    ['from' => 'customer', 'body' => 'Is anyone there? My payment went through twice.', 'minutes_ago' => 32],
                    ['from' => 'customer', 'body' => 'I can send the bank screenshots if that helps.', 'minutes_ago' => 28],
                ],
            ],
            [
                'order_number' => 'PMM-2026-1039',
                'branch_email' => UserSeeder::BRANCH_2_ADMIN,
                'order_status' => OrderStatus::Delivered,
                'with_driver' => true,
                'messages' => [
                    ['from' => 'customer', 'body' => 'Delivery instructions: please leave it with the concierge, I am at work until six.', 'minutes_ago' => 2_900],
                    ['from' => 'branch', 'body' => 'Noted on the order, the driver will see it.', 'minutes_ago' => 2_880],
                    ['from' => 'driver', 'body' => 'Handed to the concierge at the front desk, signed for by J. Alvarez.', 'minutes_ago' => 2_760],
                    ['from' => 'customer', 'body' => 'Picked it up, all correct. Thanks both.', 'minutes_ago' => 2_600],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $customer = User::query()->where('email', UserSeeder::CUSTOMER)->first();
        $driver = User::query()->where('email', UserSeeder::DRIVER)->first();

        if ($customer === null || $driver === null) {
            return;
        }

        foreach ($this->threads() as $thread) {
            $branchUser = User::query()->where('email', $thread['branch_email'])->first();

            if ($branchUser === null || $branchUser->branch_id === null) {
                continue;
            }

            $this->seedThread($thread, $customer, $driver, $branchUser);
        }
    }

    /**
     * @param  array{
     *     order_number: string,
     *     branch_email: string,
     *     order_status: OrderStatus,
     *     with_driver: bool,
     *     messages: list<array{from: string, body: string, minutes_ago: int}>
     * }  $thread
     */
    private function seedThread(
        array $thread,
        User $customer,
        User $driver,
        User $branchUser,
    ): void {
        $order = Order::query()->updateOrCreate(
            ['order_number' => $thread['order_number']],
            [
                'customer_id' => $customer->id,
                'branch_id' => $branchUser->branch_id,
                'driver_id' => $thread['with_driver'] ? $driver->id : null,
                'status' => $thread['order_status'],
            ],
        );

        $conversation = Conversation::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'branch_id' => $order->branch_id,
                'status' => $thread['order_status']->isTerminal() ? 'closed' : 'open',
                'closed_at' => $thread['order_status']->isTerminal() ? now()->subDay() : null,
            ],
        );

        // Reseeding should not stack duplicate transcripts.
        $conversation->messages()->delete();

        $this->join($conversation->id, $customer->id, ConversationParticipantRole::Customer);
        $this->join($conversation->id, $branchUser->id, ConversationParticipantRole::BranchStaff);

        if ($thread['with_driver']) {
            $this->join($conversation->id, $driver->id, ConversationParticipantRole::Driver);
        }

        $senders = [
            'customer' => $customer->id,
            'branch' => $branchUser->id,
            'driver' => $driver->id,
        ];

        $last = null;

        foreach ($thread['messages'] as $line) {
            $last = $this->writeMessage(
                $conversation->id,
                $senders[$line['from']],
                $line['body'],
                now()->subMinutes($line['minutes_ago']),
            );
        }

        if ($conversation->status->value === 'closed') {
            $last = $this->writeSystemMessage(
                $conversation->id,
                'Order '.$order->order_number.' was delivered. This conversation is now closed.',
                now()->subDay(),
            );
        }

        if ($last === null) {
            return;
        }

        $conversation->forceFill([
            'last_message_preview' => Str::limit($last->body ?? '', 150),
            'last_message_sender_id' => $last->sender_id,
            'last_message_at' => $last->created_at,
        ])->save();

        // Leave the branch operator with something unread to look at, except on
        // the closed thread which should render as fully caught up.
        $this->markBranchRead($conversation->id, $branchUser->id, $thread);
    }

    private function join(int $conversationId, int $userId, ConversationParticipantRole $role): void
    {
        ConversationParticipant::query()->updateOrCreate(
            ['conversation_id' => $conversationId, 'user_id' => $userId],
            ['role' => $role, 'joined_at' => now()->subWeek(), 'last_read_message_id' => null],
        );
    }

    private function writeMessage(
        int $conversationId,
        int $senderId,
        string $body,
        CarbonInterface $at,
    ): Message {
        $message = Message::query()->create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'type' => MessageType::Text,
            'body' => $body,
        ]);

        return $this->backdate($message, $at);
    }

    private function writeSystemMessage(int $conversationId, string $body, CarbonInterface $at): Message
    {
        $message = Message::query()->create([
            'conversation_id' => $conversationId,
            'sender_id' => null,
            'type' => MessageType::System,
            'body' => $body,
        ]);

        return $this->backdate($message, $at);
    }

    /**
     * Timestamps drive the inbox ordering, so they cannot all be "now".
     */
    private function backdate(Message $message, CarbonInterface $at): Message
    {
        $message->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();

        return $message->refresh();
    }

    /**
     * @param  array{order_status: OrderStatus, messages: list<array<string, mixed>>}  $thread
     */
    private function markBranchRead(int $conversationId, int $branchUserId, array $thread): void
    {
        $messageIds = Message::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($messageIds === []) {
            return;
        }

        $readUpTo = $thread['order_status']->isTerminal()
            ? end($messageIds)
            : $messageIds[max(0, count($messageIds) - 3)];

        ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $branchUserId)
            ->update(['last_read_message_id' => $readUpTo]);
    }
}
