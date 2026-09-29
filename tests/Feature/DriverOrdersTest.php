<?php

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use Inertia\Testing\AssertableInertia as Assert;

function driverPortalOrder(User $driver, array $fulfillment = [], array $order = []): Order
{
    $orderModel = Order::factory()->create(array_merge([
        'shipping_address_snapshot' => [
            'name' => 'Elena Vargas',
            'phone' => '+61410123456',
            'address' => '418 Crown St, Surry Hills',
            'city' => 'Surry Hills',
            'postal_code' => '2010',
        ],
        'total_amount' => 40,
        'payment_method' => 'cod',
        'time_slot' => 'Anytime',
    ], $order));

    $fulfillmentModel = OrderFulfillment::factory()->forOrder($orderModel)->create(array_merge([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Assigned,
    ], $fulfillment));

    $product = Product::factory()->create(['name' => 'Test Stick']);
    OrderItem::query()->create([
        'order_id' => $orderModel->id,
        'fulfillment_id' => $fulfillmentModel->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name],
        'quantity' => 1,
        'unit_price' => 40,
        'subtotal' => 40,
    ]);
    Payment::factory()->create([
        'order_id' => $orderModel->id,
        'status' => PaymentStatus::PendingCollection,
        'gateway' => 'cod',
        'amount' => 40,
    ]);

    return $orderModel->refresh();
}

test('guests are redirected from driver orders', function () {
    $this->get(route('driver.orders.index'))
        ->assertRedirect(route('login'));
});

test('drivers can view assigned orders index', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    driverPortalOrder($driver);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/index')
            ->has('orders', 1)
            ->has('orders.0.id')
            ->has('orders.0.orderNumber')
            ->has('orders.0.status')
            ->has('orders.0.customerName')
            ->where('orders.0.areaLabel', 'Surry Hills')
            ->has('orders.0.phone')
            ->has('orders.0.productPreview')
            ->where('orders.0.paymentMethod', 'cod'));
});

test('drivers can view an assigned order detail', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/show')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.areaLabel', 'Surry Hills')
            ->has('order.products')
            ->where('order.canPickUp', true)
            ->where('order.paymentMethod', 'cod')
            ->has('order.shippingAddress'));
});

test('guests are redirected from driver order chat', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver);

    $this->get(route('driver.orders.chat', $order))
        ->assertRedirect(route('login'));
});

test('drivers can open chat for an assigned order', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/chat')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.customerName', 'Elena Vargas')
            ->where('viewerId', $driver->id)
            ->where('canSend', false)
            ->has('messages'));
});

test('unknown driver order chats return 404', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', 'missing-order'))
        ->assertNotFound();
});

test('delivered order chat is read-only', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/chat')
            ->where('order.status', 'delivered')
            ->where('canSend', false)
            ->has('messages'));
});

test('drivers can view old orders history', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/history')
            ->has('orders', 1)
            ->where('orders.0.customerName', 'Elena Vargas')
            ->where('orders.0.status', 'delivered'));
});

test('guests are redirected from old orders', function () {
    $this->get(route('driver.history.index'))
        ->assertRedirect(route('login'));
});

test('drivers can view a past order detail', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/show')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.areaLabel', 'Surry Hills')
            ->where('order.status', 'delivered'));
});

test('unknown driver orders return 404', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', 'missing-order'))
        ->assertNotFound();
});

test('a driver cannot open another drivers assigned order', function () {
    $owner = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $other = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($owner);

    $this->actingAs($other, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertNotFound();

    $this->actingAs($other, 'driver')
        ->get(route('driver.orders.chat', $order))
        ->assertNotFound();
});

test('deliver rejects a short otp', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::OutForDelivery]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.deliver', $order), [
            'otp' => '12',
        ])
        ->assertSessionHasErrors('otp')
        ->assertRedirect(route('driver.orders.show', $order));
});

test('deliver accepts a valid otp and flashes success', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::PickedUp]);

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.start', $order))
        ->assertRedirect();

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.deliver', $order), [
            'otp' => $sms->lastCode(),
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect(route('driver.orders.index'));
});

test('driver dashboard receives assigned order summary', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    driverPortalOrder($driver);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/dashboard')
            ->has('summary.activeCount')
            ->has('summary.deliveredCount')
            ->has('summary.assignedCount')
            ->has('summary.activeOrders')
            ->where('summary.nextStop.customerName', 'Elena Vargas')
            ->where('summary.nextStop.addressSummary', '418 Crown St, Surry Hills')
            ->has('hub.shift.label')
            ->has('hub.earnings.todayLabel')
            ->has('hub.performance.rating')
            ->has('hub.vehicle.name')
            ->has('hub.alerts'));
});
