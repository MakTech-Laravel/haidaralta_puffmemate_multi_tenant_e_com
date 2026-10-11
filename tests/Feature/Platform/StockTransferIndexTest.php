<?php

use App\Enums\StockTransferStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('platform stock transfers are paginated and can be filtered by status and order number', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $source = Branch::factory()->create(['name' => 'Source Depot']);
    $destination = Branch::factory()->create(['name' => 'Destination Shop']);
    $product = Product::factory()->create(['name' => 'Transfer Mint']);
    $order = Order::factory()->create(['order_number' => 'PMM-XFER-1001']);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $destination->id,
    ]);
    $match = StockTransfer::factory()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'source_branch_id' => $source->id,
        'destination_branch_id' => $destination->id,
        'status' => StockTransferStatus::Requested,
    ]);
    $match->items()->create([
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 2,
    ]);
    StockTransfer::factory()->create([
        'source_branch_id' => $source->id,
        'destination_branch_id' => $destination->id,
        'status' => StockTransferStatus::Received,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.stock-transfers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/stock-transfers/index')
            ->has('table.data', 2)
            ->where('table.meta.per_page', 15));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.stock-transfers.index', [
            'filter' => ['status' => StockTransferStatus::Requested->value],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $match->id)
            ->where('table.data.0.items_label', 'Transfer Mint × 2'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.stock-transfers.index', [
            'filter' => ['search' => 'PMM-XFER-1001'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $match->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.stock-transfers.index', [
            'filter' => ['search' => 'Destination Shop'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('table.data', 2));
});
