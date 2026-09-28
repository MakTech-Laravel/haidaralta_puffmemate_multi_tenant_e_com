<?php

namespace Database\Factories;

use App\Enums\ConversationStatus;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'branch_id' => Branch::factory(),
            'status' => ConversationStatus::Open,
            'last_message_at' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Uses the order's first fulfillment branch when one exists, otherwise a
     * fresh branch (order has no fulfillment yet in this test scenario).
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (): array => [
            'order_id' => $order->id,
            'branch_id' => $order->fulfillments()->oldest()->value('branch_id') ?? Branch::factory(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => ConversationStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
