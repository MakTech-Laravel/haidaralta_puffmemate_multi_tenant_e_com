<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => NotificationType::Order,
            'title' => 'Order updated',
            'message' => 'Your order status changed.',
            'data' => null,
            'created_by' => null,
        ];
    }
}
