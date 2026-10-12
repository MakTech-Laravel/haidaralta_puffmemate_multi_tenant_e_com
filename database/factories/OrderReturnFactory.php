<?php

namespace Database\Factories;

use App\Enums\OrderReturnStatus;
use App\Enums\OrderReturnType;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'requester_id' => User::factory()->state(['user_type' => UserType::CUSTOMER, 'branch_id' => null]),
            'type' => OrderReturnType::Refund,
            'reason' => 'Damaged goods',
            'note' => null,
            'status' => OrderReturnStatus::Requested,
        ];
    }
}
