<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchArea;
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
use App\Services\Sms\FakeSmsSender;
use App\Support\Geo\Polygon;
use Database\Seeders\ApplicationSettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
});

test('a sydney customer with a late line gets one area fulfillment without a stock transfer', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['slug' => 'sydney-a', 'selling_price' => 20, 'status' => true]);
    $productB = Product::factory()->create(['slug' => 'sydney-b', 'selling_price' => 20, 'status' => true]);
    $productC = Product::factory()->create(['slug' => 'sydney-c', 'selling_price' => 20, 'status' => true]);
    $productD = Product::factory()->create(['slug' => 'late-d', 'selling_price' => 20, 'status' => true]);

    $sydney = Branch::factory()->create([
        'name' => 'Sydney CBD',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
        'fulfillment_priority' => 1,
    ]);
    BranchArea::factory()->forBranch($sydney)->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    foreach ([$productA, $productB, $productC] as $product) {
        Inventory::factory()->create([
            'branch_id' => $sydney->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'status' => true,
        ]);
    }

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
            'accept_late_delivery' => true,
            'terms' => true,
        ])
        ->assertRedirect();

    $order = Order::query()->firstOrFail();
    $lateItem = $order->items->firstWhere('product_id', $productD->id);

    expect($order->fulfillments)->toHaveCount(1)
        ->and($order->fulfillment->branch_id)->toBe($sydney->id)
        ->and($order->items)->toHaveCount(4)
        ->and($order->accepts_late_delivery)->toBeTrue()
        ->and($lateItem?->is_late_delivery)->toBeTrue()
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payment->status)->toBe(PaymentStatus::PendingCollection)
        ->and(StockTransfer::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and(Conversation::query()->where('order_id', $order->id)->count())->toBe(0);

    $this->actingAs($user, 'customer')
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.hasLateDeliveryItems', true)
            ->missing('order.branch')
            ->missing('order.fulfillments')
            ->missing('order.transfers')
            ->missing('order.branchName'));

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
