<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Services\Order\DriverAssignmentService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\StockTransferService;
use App\Services\Sms\FakeSmsSender;
use Database\Seeders\ApplicationSettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
});

test('a sydney customer with mixed sydney and melbourne stock gets one fulfillment and one transfer', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['slug' => 'sydney-a', 'selling_price' => 20, 'status' => true]);
    $productB = Product::factory()->create(['slug' => 'sydney-b', 'selling_price' => 20, 'status' => true]);
    $productC = Product::factory()->create(['slug' => 'sydney-c', 'selling_price' => 20, 'status' => true]);
    $productD = Product::factory()->create(['slug' => 'melbourne-d', 'selling_price' => 20, 'status' => true]);

    $sydney = Branch::factory()->create([
        'name' => 'Sydney CBD',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
        'fulfillment_priority' => 1,
    ]);
    $melbourne = Branch::factory()->create([
        'name' => 'Melbourne Collins',
        'latitude' => -37.8136,
        'longitude' => 144.9631,
        'delivery_radius_km' => null,
        'status' => true,
        'fulfillment_priority' => 50,
    ]);

    foreach ([$productA, $productB, $productC] as $product) {
        Inventory::factory()->create([
            'branch_id' => $sydney->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'status' => true,
        ]);
    }

    Inventory::factory()->create([
        'branch_id' => $melbourne->id,
        'product_id' => $productD->id,
        'quantity' => 5,
        'status' => true,
    ]);

    foreach ([$productA, $productB, $productC, $productD] as $product) {
        $this->actingAs($user, 'customer')
            ->post(route('cart.store'), [
                'product' => $product->slug,
                'quantity' => 1,
            ]);
    }

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), [
            'name' => 'Sydney Customer',
            'mobile' => '0410123456',
            'address' => '1 George Street',
            'city' => 'Sydney',
            'postal_code' => '2000',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'delivery_method' => 'regular',
            'payment_method' => PaymentGatewaySetting::KEY_COD,
            'terms' => true,
        ])
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->fulfillments)->toHaveCount(1)
        ->and($order->fulfillment->branch_id)->toBe($sydney->id)
        ->and($order->items)->toHaveCount(4)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payment->status)->toBe(PaymentStatus::PendingCollection)
        ->and(Conversation::query()->where('order_id', $order->id)->count())->toBe(0);

    $transfer = StockTransfer::query()->where('order_id', $order->id)->firstOrFail();

    expect($transfer->source_branch_id)->toBe($melbourne->id)
        ->and($transfer->destination_branch_id)->toBe($sydney->id)
        ->and($transfer->items)->toHaveCount(1)
        ->and($transfer->items->first()->product_id)->toBe($productD->id);

    $this->actingAs($user, 'customer')
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->missing('order.branch')
            ->missing('order.fulfillments')
            ->missing('order.transfers')
            ->missing('order.branchName'));

    $melbourneStaff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $melbourne->id,
    ]);
    $sydneyStaff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $sydney->id,
    ]);
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->available()->create(['user_id' => $driver->id]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($driver, $sydney);

    $transfers = app(StockTransferService::class);
    $transfers->approve($transfer, $melbourneStaff);
    $transfers->pick($transfer->refresh(), $melbourneStaff);
    $transfers->dispatch($transfer->refresh(), $melbourneStaff);
    $transfers->receive($transfer->refresh(), $sydneyStaff);

    $fulfillment = $order->fulfillment->refresh();
    $statuses = app(FulfillmentStatusService::class);
    $statuses->transition($fulfillment, FulfillmentStatus::Placed, $sydneyStaff);
    $statuses->transition($fulfillment->refresh(), FulfillmentStatus::Processing, $sydneyStaff);
    $statuses->transition($fulfillment->refresh(), FulfillmentStatus::Ready, $sydneyStaff);
    app(DriverAssignmentService::class)->assign($fulfillment->refresh(), $driver, $sydneyStaff);

    $deliveries = app(DriverDeliveryService::class);
    $deliveries->pickUp($fulfillment->refresh(), $driver);
    $deliveries->startDelivery($fulfillment->refresh(), $driver);
    $deliveries->arrive($fulfillment->refresh(), $driver);
    $deliveries->complete($fulfillment->refresh(), $driver, app(FakeSmsSender::class)->lastCode());

    expect($order->refresh()->fulfillments)->toHaveCount(1)
        ->and($order->fulfillment->branch_id)->toBe($sydney->id)
        ->and($order->fulfillment->driver_id)->toBe($driver->id)
        ->and($order->fulfillment->status)->toBe(FulfillmentStatus::Delivered)
        ->and($order->payment->refresh()->status)->toBe(PaymentStatus::Collected)
        ->and($order->delivery_fee)->not->toBe((float) $transfer->estimated_cost)
        ->and(Conversation::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(Payment::query()->where('order_id', $order->id)->count())->toBe(1);

    $this->actingAs($user, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/show')
            ->where('order.statusLabel', 'Delivered')
            ->missing('order.branch')
            ->missing('order.branchName')
            ->missing('order.transfers')
            ->missing('order.sourceBranch')
            ->missing('order.driver_id'));
});
