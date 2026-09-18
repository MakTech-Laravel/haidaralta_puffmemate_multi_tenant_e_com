<?php

namespace Database\Factories;

use App\Enums\ConversationStatus;
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
        $order = Order::factory();

        return [
            'order_id' => $order,
            'branch_id' => fn (array $attributes): int => Order::query()
                ->whereKey($attributes['order_id'])
                ->value('branch_id'),
            'status' => ConversationStatus::Open,
            'last_message_at' => null,
            'closed_at' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (): array => [
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
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
