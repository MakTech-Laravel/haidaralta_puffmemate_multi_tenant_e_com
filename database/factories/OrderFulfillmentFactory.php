<?php

namespace Database\Factories;

use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderFulfillment>
 */
class OrderFulfillmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'branch_id' => Branch::factory(),
            'driver_id' => null,
            'fulfillment_number' => 'PMM-'.fake()->unique()->numerify('####-####').'-A',
            'status' => FulfillmentStatus::Pending,
            'distance_km' => null,
            'shipping_cost' => 0,
        ];
    }

    public function status(FulfillmentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function delivered(): static
    {
        return $this->status(FulfillmentStatus::Delivered);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (): array => ['order_id' => $order->id]);
    }

    public function withDriver(?User $driver = null): static
    {
        return $this->state(fn (): array => [
            'driver_id' => $driver?->id ?? User::factory()->state([
                'user_type' => UserType::DRIVER,
                'branch_id' => null,
            ]),
        ]);
    }
}
