<?php

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Services\Order\OrderPlacementNotifier;
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

test('drivers can open chat once the order is on the way', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::OutForDelivery]);
    app(ConversationService::class)->openForOrder($order->fresh('fulfillment'));

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/chat')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.customerName', 'Elena Vargas')
            ->where('viewerId', $driver->id)
            ->where('canSend', true)
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
            ->has('orders.data', 1)
            ->where('orders.data.0.customerName', 'Elena Vargas')
            ->where('orders.data.0.status', 'delivered')
            ->where('orders.data.0.completedLabel', 'Today'));
});

test('order history keeps finished drops only and searches on the server', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Returned]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Cancelled]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::OutForDelivery]);
    $match = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered], [
        'shipping_address_snapshot' => [
            'name' => 'Marcus Lee',
            'phone' => '+61410999888',
            'address' => '9 Bondi Rd, Bondi',
            'city' => 'Bondi',
        ],
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 4)
            ->where('total', 4)
            ->where('filters.search', ''));

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index', ['search' => 'marcus']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', $match->order_number)
            ->where('filters.search', 'marcus'));
});

test('order history defaults to the running month and filters by a date range', function () {
    $this->travelTo(now()->setDate(2026, 10, 14)->setTime(12, 0));
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $earlyOctober = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    $lateSeptember = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    $earlyOctober->fulfillment->forceFill(['updated_at' => now()->setDate(2026, 10, 3)])->saveQuietly();
    $lateSeptember->fulfillment->forceFill(['updated_at' => now()->setDate(2026, 9, 28)])->saveQuietly();

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 2)
            ->where('total', 2)
            ->where('filters.from', '2026-10-01')
            ->where('filters.to', '2026-10-14')
            ->where('today', '2026-10-14'));

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index', ['from' => '2026-09-25', 'to' => '2026-10-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 2)
            ->where('orders.data.0.orderNumber', $earlyOctober->order_number)
            ->where('orders.data.1.orderNumber', $lateSeptember->order_number));

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index', ['from' => '2026-10-03', 'to' => '2026-10-03']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', $earlyOctober->order_number));

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index', ['from' => '2026-10-05', 'to' => '2026-09-25']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.from', '2026-09-25')
            ->where('filters.to', '2026-10-05')
            ->has('orders.data', 2));
});

test('order history rejects future dates', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index', [
            'from' => now()->format('Y-m-d'),
            'to' => now()->addDays(3)->format('Y-m-d'),
        ]))
        ->assertSessionHasErrors('to');
});

test('order history pages through older drops', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    foreach (range(1, 16) as $ignored) {
        driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    }

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('orders.data', 1));
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
    $order->forceFill(['status' => OrderStatus::PickedUp])->save();
    app(OrderPlacementNotifier::class)->issue($order->refresh());

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.start', $order))
        ->assertRedirect();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.arrive', $order))
        ->assertRedirect();

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.deliver', $order), [
            'otp' => $sms->lastCode(),
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Order marked as delivered.',
        ])
        ->assertRedirect(route('driver.orders.index'));
});

test('driver orders index lists the live run first and only todays deliveries', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $assigned = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned]);
    $arrived = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived]);
    $deliveredToday = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    $deliveredYesterday = driverPortalOrder($driver, ['status' => FulfillmentStatus::Delivered]);
    $deliveredYesterday->fulfillment->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Cancelled]);
    driverPortalOrder($driver, ['status' => FulfillmentStatus::Returned]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/index')
            ->has('orders', 3)
            ->where('orders.0.orderNumber', $arrived->order_number)
            ->where('orders.0.statusLabel', 'Arrived')
            ->where('orders.0.canChat', true)
            ->where('orders.0.nextAction.key', 'complete')
            ->where('orders.1.orderNumber', $assigned->order_number)
            ->where('orders.1.canChat', false)
            ->where('orders.1.nextAction.key', 'pick_up')
            ->where('orders.2.orderNumber', $deliveredToday->order_number)
            ->where('orders.2.status', 'delivered')
            ->where('orders.2.nextAction', null));
});

test('driver can pick up an order from the list and stays on the list', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.index'))
        ->post(route('driver.orders.pick-up', $order))
        ->assertRedirect(route('driver.orders.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Order picked up.',
        ]);

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::PickedUp);
});

test('driver cannot skip a delivery step from the list', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.index'))
        ->post(route('driver.orders.arrive', $order))
        ->assertRedirect(route('driver.orders.index'))
        ->assertSessionHasErrors('status');

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Assigned);
});

test('driver can mark an arrived order as not delivered for another reason', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.fail', $order), [
            'reason' => DeliveryFailureReason::Other->value,
            'note' => 'Building is locked and the gate code does not work',
        ])
        ->assertRedirect(route('driver.orders.show', $order))
        ->assertSessionHasNoErrors();

    $fulfillment = $order->fulfillment->fresh();

    expect($fulfillment->status)->toBe(FulfillmentStatus::DeliveryFailed)
        ->and($fulfillment->deliveryAttempts->sole()->reason)->toBe(DeliveryFailureReason::Other)
        ->and($fulfillment->deliveryAttempts->sole()->note)->toBe('Building is locked and the gate code does not work');
});

test('driver cannot mark an order as not delivered with an unknown reason', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.fail', $order), [
            'reason' => 'unable_to_complete',
            'note' => 'Something happened',
        ])
        ->assertRedirect(route('driver.orders.show', $order))
        ->assertSessionHasErrors('reason');

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Arrived);
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
