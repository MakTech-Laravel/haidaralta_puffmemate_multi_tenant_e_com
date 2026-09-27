<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'PMM-'.fake()->unique()->numerify('####-####'),
            'customer_id' => User::factory()->state(['user_type' => UserType::CUSTOMER, 'branch_id' => null]),
            'status' => OrderStatus::Pending,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function delivered(): static
    {
        return $this->status(OrderStatus::Delivered);
    }

    public function cancelled(): static
    {
        return $this->status(OrderStatus::Cancelled);
    }
}
