<?php

use App\Enums\StockTransferStatus;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function stockTransferBetween(Branch $source, Branch $destination, StockTransferStatus $status = StockTransferStatus::Requested): StockTransfer
{
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'branch_id' => $source->id,
        'product_id' => $product->id,
        'quantity' => 4,
        'reserved_quantity' => 1,
    ]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $destination->id,
    ]);

    $transfer = StockTransfer::factory()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'source_branch_id' => $source->id,
        'destination_branch_id' => $destination->id,
        'status' => $status,
        'estimated_cost' => 12.5,
    ]);
    $transfer->items()->create([
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 1,
    ]);

    return $transfer->refresh();
}

test('source branch staff can approve pick and dispatch a transfer', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_2_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $transfer = stockTransferBetween($melbourne, $sydney);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.stock-transfers.index', $melbourne))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/stock-transfers/index')
            ->has('table.data', 1)
            ->where('table.data.0.can_approve', true)
            ->where('table.meta.per_page', 15));

    $this->actingAs($admin, 'branch')
        ->post(route('branch.stock-transfers.approve', [$melbourne, $transfer]))
        ->assertRedirect();
    $this->actingAs($admin, 'branch')
        ->post(route('branch.stock-transfers.pick', [$melbourne, $transfer]))
        ->assertRedirect();
    $this->actingAs($admin, 'branch')
        ->post(route('branch.stock-transfers.dispatch', [$melbourne, $transfer]))
        ->assertRedirect();

    expect($transfer->refresh()->status)->toBe(StockTransferStatus::InTransit);
});

test('destination branch staff can receive an in-transit transfer', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $transfer = stockTransferBetween($melbourne, $sydney, StockTransferStatus::InTransit);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.stock-transfers.receive', [$sydney, $transfer]))
        ->assertRedirect();

    expect($transfer->refresh()->status)->toBe(StockTransferStatus::Received);
});

test('a branch cannot act on another branchs transfer', function () {
    $sydneyAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $transfer = stockTransferBetween($melbourne, $sydney);

    $this->actingAs($sydneyAdmin, 'branch')
        ->post(route('branch.stock-transfers.approve', [$sydney, $transfer]))
        ->assertNotFound();
});
