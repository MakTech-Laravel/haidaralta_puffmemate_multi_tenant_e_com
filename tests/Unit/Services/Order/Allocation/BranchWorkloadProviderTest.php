<?php

use App\Enums\FulfillmentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Order\Allocation\BranchWorkloadProvider;

test('counts only open fulfillments per branch via a single grouped aggregate query', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    OrderFulfillment::factory()->create([
        'branch_id' => $branchA->id,
        'order_id' => Order::factory()->create()->id,
        'status' => FulfillmentStatus::Pending,
    ]);
    OrderFulfillment::factory()->create([
        'branch_id' => $branchA->id,
        'order_id' => Order::factory()->create()->id,
        'status' => FulfillmentStatus::Delivered,
    ]);
    OrderFulfillment::factory()->create([
        'branch_id' => $branchB->id,
        'order_id' => Order::factory()->create()->id,
        'status' => FulfillmentStatus::Cancelled,
    ]);

    $counts = app(BranchWorkloadProvider::class)->openFulfillmentCounts([$branchA->id, $branchB->id]);

    expect($counts)->toBe([$branchA->id => 1]);
});

test('an empty branch id list short-circuits without a query', function () {
    expect(app(BranchWorkloadProvider::class)->openFulfillmentCounts([]))->toBe([]);
});
