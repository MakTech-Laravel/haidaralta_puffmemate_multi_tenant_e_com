<?php

namespace Database\Factories;

use App\Models\DeliveryMethod;
use App\Models\DeliveryTimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryTimeSlot>
 */
class DeliveryTimeSlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_method_id' => DeliveryMethod::factory(),
            'label' => '9:00 AM – 12:00 PM',
            'status' => true,
            'sort' => 0,
        ];
    }
}
