<?php

namespace Database\Seeders;

use App\Enums\ConversationParticipantRole;
use App\Enums\ConversationStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\MessageType;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bulk conversations so both infinite scrollers can actually be exercised.
 *
 * `OrderConversationSeeder` supplies the four readable showcase threads; this
 * one supplies volume: enough rows to page the inbox several times and one
 * thread long enough to page the transcript repeatedly.
 *
 * No faker. The copy comes from hand-written phrase banks walked
 * deterministically, so a re-seed reproduces the same transcript byte for byte
 * while still reading like a delivery conversation.
 */
class ConversationVolumeSeeder extends Seeder
{
    /** Threads created per branch. */
    private const THREADS_PER_BRANCH = 48;

    /** Messages in the single deep thread used to test transcript paging. */
    private const DEEP_THREAD_MESSAGES = 720;

    /** Orders left without a conversation, for the New conversation picker. */
    private const SPARE_ORDERS = 18;

    /**
     * @return list<string>
     */
    private function customerLines(): array
    {
        return [
            'Hi, has my order left the store yet?',
            'Can you confirm the delivery window for today?',
            'I will not be home until six, can the driver come after that?',
            'The tracking has not moved for an hour. Is everything alright?',
            'Could you add a note for the driver to use the side entrance?',
            'I ordered two packs but the confirmation only shows one.',
            'Is the menthol variant back in stock yet?',
            'Please leave it with the building concierge if I do not answer.',
            'My buzzer is broken, please call me on arrival instead.',
            'Thanks for the quick reply, that clears it up.',
            'Can I change the delivery address to my work office?',
            'Do you accept a card payment on delivery?',
            'The invoice total looks higher than what I saw at checkout.',
            'Sorry, one more question before you dispatch it.',
            'Received it, everything looks correct. Appreciate the help.',
            'Can you hold the order until I finish work at seven?',
            'The building has a loading dock on York Lane, not the front door.',
            'I need a tax invoice emailed for my employer reimbursement.',
            'Is the 20mg mango still the one on the shelf photo?',
            'Driver called but I missed it — are they coming back around?',
            'Please swap the menthol for the berry blend if you still have stock.',
            'Confirming I am home now, send the driver whenever ready.',
        ];
    }

    /**
     * @return list<string>
     */
    private function branchLines(): array
    {
        return [
            'Thanks for reaching out, let me pull up the order.',
            'It is packed and waiting for the next driver run.',
            'I have added that note to the delivery instructions.',
            'Checking with the floor team now, one moment.',
            'Confirmed, the second pack is in the same box.',
            'That variant lands in tomorrow morning\'s restock.',
            'The delivery window is between four and six this afternoon.',
            'I have updated the address on the order.',
            'Card on delivery is fine, the driver carries a terminal.',
            'The difference is the second item, no extra delivery charge.',
            'Apologies for the delay, we were short one driver this morning.',
            'You should see the status change within a few minutes.',
            'Happy to help, let us know if anything else comes up.',
            'I have flagged this for the shift supervisor.',
            'All sorted on our side.',
            'I have put a hold on the order until seven as requested.',
            'York Lane note is on the delivery slip now.',
            'Tax invoice is attached to the order confirmation email.',
            'Yes, that shelf photo is the 20mg mango SKU.',
            'The driver will loop back on the next pass, about twenty minutes.',
            'Swapped to berry blend, same strength, no price change.',
            'Great, assigning the next available courier now.',
        ];
    }

    /**
     * @return list<string>
     */
    private function driverLines(): array
    {
        return [
            'Picked up, heading your way now.',
            'About fifteen minutes out.',
            'Stuck in traffic on the bridge, running a little late.',
            'I am downstairs at the main entrance.',
            'No answer on the buzzer, trying your mobile.',
            'Left with the concierge as requested.',
            'Delivered, thanks for your patience.',
            'Taking the side entrance as noted.',
            'Next stop is yours, roughly ten minutes.',
        ];
    }

    public function run(): void
    {
        $customer = User::query()->where('email', UserSeeder::CUSTOMER)->first();
        $driver = User::query()->where('email', UserSeeder::DRIVER)->first();

        if ($customer === null || $driver === null) {
            return;
        }

        $branches = Branch::query()->orderBy('id')->get();
        $sequence = 2000;

        foreach ($branches as $branch) {
            $operator = $this->operatorFor($branch);

            if ($operator === null) {
                continue;
            }

            for ($index = 0; $index < self::THREADS_PER_BRANCH; $index++) {
                $this->buildThread(
                    orderNumber: 'PMM-2026-'.(++$sequence),
                    branch: $branch,
                    customer: $customer,
                    driver: $driver,
                    operator: $operator,
                    messageCount: 6 + ($index % 9),
                    ageInHours: 3 + ($index * 7),
                    closed: $index % 5 === 4,
                );
            }
        }

        $firstBranch = $branches->first();

        if ($firstBranch !== null) {
            $operator = $this->operatorFor($firstBranch);

            if ($operator !== null) {
                $this->buildThread(
                    orderNumber: 'PMM-2026-9000',
                    branch: $firstBranch,
                    customer: $customer,
                    driver: $driver,
                    operator: $operator,
                    messageCount: self::DEEP_THREAD_MESSAGES,
                    ageInHours: 1,
                    closed: false,
                );
            }
        }

        $this->seedSpareOrders($branches->all(), $customer, ++$sequence);
    }

    /**
     * Orders with no thread, so the New conversation picker is never empty.
     *
     * @param  array<int, Branch>  $branches
     */
    private function seedSpareOrders(array $branches, User $customer, int $sequence): void
    {
        $branches = array_values($branches);

        if ($branches === []) {
            return;
        }

        for ($index = 0; $index < self::SPARE_ORDERS; $index++) {
            $branch = $branches[$index % count($branches)];

            $order = Order::query()->updateOrCreate(
                ['order_number' => 'PMM-2026-'.($sequence + $index)],
                [
                    'customer_id' => $customer->id,
                    'status' => $index % 2 === 0 ? OrderStatus::Pending : OrderStatus::Processing,
                ],
            );

            OrderFulfillment::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'branch_id' => $branch->id,
                    'driver_id' => null,
                    'fulfillment_number' => $order->order_number,
                    'status' => FulfillmentStatus::Pending,
                ],
            );
        }
    }

    private function buildThread(
        string $orderNumber,
        Branch $branch,
        User $customer,
        User $driver,
        User $operator,
        int $messageCount,
        int $ageInHours,
        bool $closed,
    ): void {
        $order = Order::query()->updateOrCreate(
            ['order_number' => $orderNumber],
            [
                'customer_id' => $customer->id,
                'status' => $closed ? OrderStatus::Delivered : OrderStatus::Processing,
            ],
        );

        OrderFulfillment::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'branch_id' => $branch->id,
                'driver_id' => $driver->id,
                'fulfillment_number' => $order->order_number,
                'status' => $closed ? FulfillmentStatus::Delivered : FulfillmentStatus::Processing,
            ],
        );

        $conversation = Conversation::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'branch_id' => $branch->id,
                'status' => $closed ? ConversationStatus::Closed : ConversationStatus::Open,
                'closed_at' => $closed ? now()->subHours($ageInHours) : null,
            ],
        );

        if ($messageCount < 1) {
            return;
        }

        $conversation->messages()->delete();

        $this->join($conversation->id, $customer->id, ConversationParticipantRole::Customer);
        $this->join($conversation->id, $driver->id, ConversationParticipantRole::Driver);
        $this->join($conversation->id, $operator->id, ConversationParticipantRole::BranchStaff);

        $rows = $this->composeMessages(
            $conversation->id,
            [$customer->id, $operator->id, $driver->id],
            $messageCount,
            $ageInHours,
        );

        // Chunked so the deep thread does not build one enormous insert.
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('messages')->insert($chunk);
        }

        $last = $rows[count($rows) - 1];

        $conversation->forceFill([
            'last_message_preview' => Str::limit((string) $last['body'], 150),
            'last_message_sender_id' => $last['sender_id'],
            'last_message_at' => $last['created_at'],
        ])->save();

        $this->seedReceipts($conversation->id, $operator->id, $customer->id, $driver->id);
    }

    /**
     * Walk the phrase banks in a fixed rotation so the exchange alternates
     * between the three voices and reads in order.
     *
     * @param  list<int>  $senderIds  customer, operator, driver
     * @return list<array<string, mixed>>
     */
    private function composeMessages(
        int $conversationId,
        array $senderIds,
        int $messageCount,
        int $ageInHours,
    ): array {
        $banks = [$this->customerLines(), $this->branchLines(), $this->driverLines()];
        $rows = [];

        for ($index = 0; $index < $messageCount; $index++) {
            $voice = $index % 3;
            $bank = $banks[$voice];
            $body = $bank[intdiv($index, 3) % count($bank)];

            // Oldest first, two minutes apart, ending `$ageInHours` ago.
            $at = now()
                ->subHours($ageInHours)
                ->subMinutes(2 * ($messageCount - $index));

            $rows[] = [
                'conversation_id' => $conversationId,
                'sender_id' => $senderIds[$voice],
                'type' => MessageType::Text->value,
                'body' => $body,
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        return $rows;
    }

    /**
     * Leave the operator a few unread, and give the other two partial
     * delivery/read markers so every tick state is visible in the UI.
     */
    private function seedReceipts(int $conversationId, int $operatorId, int $customerId, int $driverId): void
    {
        $ids = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return;
        }

        $count = count($ids);
        $newest = $ids[$count - 1];

        $this->setMarkers($conversationId, $operatorId, $newest, $ids[max(0, $count - 4)]);
        $this->setMarkers($conversationId, $customerId, $newest, $ids[max(0, $count - 2)]);
        $this->setMarkers($conversationId, $driverId, $ids[max(0, $count - 2)], $ids[max(0, $count - 6)]);
    }

    private function setMarkers(int $conversationId, int $userId, int $delivered, int $read): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update([
                'last_delivered_message_id' => $delivered,
                'last_read_message_id' => $read,
            ]);
    }

    private function join(int $conversationId, int $userId, ConversationParticipantRole $role): void
    {
        ConversationParticipant::query()->updateOrCreate(
            ['conversation_id' => $conversationId, 'user_id' => $userId],
            ['role' => $role, 'joined_at' => now()->subWeek()],
        );
    }

    private function operatorFor(Branch $branch): ?User
    {
        return User::query()
            ->where('branch_id', $branch->id)
            ->orderBy('id')
            ->first();
    }
}
