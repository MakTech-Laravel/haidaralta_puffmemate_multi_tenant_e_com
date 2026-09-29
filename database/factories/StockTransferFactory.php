<?php

namespace Database\Factories;

use App\Enums\StockTransferStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'fulfillment_id' => OrderFulfillment::factory(),
            'source_branch_id' => Branch::factory(),
            'destination_branch_id' => Branch::factory(),
            'status' => StockTransferStatus::Requested,
            'estimated_cost' => 0,
            'distance_km' => 10,
            'eta_hours' => 1,
        ];
    }
}
