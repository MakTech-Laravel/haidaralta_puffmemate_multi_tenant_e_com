<?php

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Message;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Sms\FakeSmsSender;
use Inertia\Testing\AssertableInertia as Assert;

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

test('driver order detail exposes coupon discount labels', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned], [
        'subtotal' => 50,
        'discount' => 10,
        'coupon_code' => 'SAVE10',
        'delivery_fee' => 5,
        'total_amount' => 45,
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/show')
            ->where('order.subtotalLabel', '$50.00')
            ->where('order.discountLabel', '$10.00')
            ->where('order.couponCode', 'SAVE10')
            ->where('order.deliveryFeeLabel', '$5.00')
            ->where('order.totalLabel', '$45.00'));
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
            ->where('channel', $order->fresh()->conversation->channelName())
            ->has('messages'));
});

test('drivers can message the customer once the order is on the way', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::OutForDelivery]);
    app(ConversationService::class)->openForOrder($order->fresh('fulfillment'));

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.chat', $order))
        ->post(route('driver.orders.messages.store', $order), ['body' => 'Five minutes away.'])
        ->assertRedirect(route('driver.orders.chat', $order));

    expect(Message::query()
        ->where('conversation_id', $order->fresh()->conversation->id)
        ->where('sender_id', $driver->id)
        ->where('body', 'Five minutes away.')
        ->exists())->toBeTrue();
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

test('cancelled orders stay on the last assignee history after driver_id is cleared', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned]);
    $fulfillment = $order->fulfillment;

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $driver->id,
        'reason' => 'Assigned',
        'changed_by' => $staff->id,
    ]);

    app(FulfillmentStatusService::class)->cancel($fulfillment, $staff, 'Branch cancelled');

    expect($fulfillment->refresh()->driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Cancelled);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', $order->order_number)
            ->where('orders.data.0.status', 'cancelled'));

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertOk();
});

test('returned orders stay on the last assignee history after driver_id is cleared', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::DeliveryFailed]);
    $fulfillment = $order->fulfillment;

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $driver->id,
        'reason' => 'Assigned',
        'changed_by' => $staff->id,
    ]);

    app(DriverDeliveryService::class)->beginReturn($fulfillment, $staff);
    app(DriverDeliveryService::class)->confirmReturn($fulfillment->refresh(), $staff);

    expect($fulfillment->refresh()->driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Returned);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', $order->order_number)
            ->where('orders.data.0.status', 'returned'));
});

test('a previous assignee does not see a cancelled drop after reassignment', function () {
    $first = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $second = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($second, ['status' => FulfillmentStatus::Assigned]);
    $fulfillment = $order->fulfillment;

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $first->id,
        'reason' => 'Assigned',
        'changed_by' => $staff->id,
    ]);
    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => $first->id,
        'to_driver_id' => $second->id,
        'reason' => 'Reassigned',
        'changed_by' => $staff->id,
    ]);

    app(FulfillmentStatusService::class)->cancel($fulfillment, $staff, 'Branch cancelled');

    expect($fulfillment->refresh()->driver_id)->toBeNull();

    $this->actingAs($second, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.orderNumber', $order->order_number));

    $this->actingAs($first, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 0)
            ->where('total', 0));

    $this->actingAs($first, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertNotFound();
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

test('the delivery code still works days after the order was placed', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived], ['status' => OrderStatus::Arrived]);
    app(OrderPlacementNotifier::class)->issue($order);
    $code = app(FakeSmsSender::class)->lastCode();

    $this->travel(3)->days();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.deliver', $order), ['otp' => $code])
        ->assertSessionHasNoErrors();

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Delivered);
});

test('a wrong delivery code shows an error on the code field and locks after five tries', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived], ['status' => OrderStatus::Arrived]);
    app(OrderPlacementNotifier::class)->issue($order);
    $code = app(FakeSmsSender::class)->lastCode();
    $wrongCode = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $ignored) {
        $this->actingAs($driver, 'driver')
            ->from(route('driver.orders.show', $order))
            ->post(route('driver.orders.deliver', $order), ['otp' => $wrongCode])
            ->assertSessionHasErrors(['otp' => 'This verification code is invalid or has expired.']);
    }

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.deliver', $order), ['otp' => $code])
        ->assertSessionHasErrors(['otp' => 'Too many attempts. Please request a new code.']);

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Arrived);
});

test('driver can send the customer a new delivery code at the door', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived], ['status' => OrderStatus::Arrived]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.delivery-code', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'A new code was sent to the customer.',
        ])
        ->assertRedirect(route('driver.orders.show', $order));

    $code = app(FakeSmsSender::class)->lastCode();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.deliver', $order), ['otp' => $code])
        ->assertSessionHasNoErrors();

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Delivered);
});

test('driver can send a new delivery code only after arriving', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::OutForDelivery], ['status' => OrderStatus::OutForDelivery]);

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.delivery-code', $order))
        ->assertSessionHasErrors('code');

    expect(app(FakeSmsSender::class)->messages)->toBeEmpty();
});

test('a driver cannot send a delivery code for another drivers order', function () {
    $owner = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $other = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($owner, ['status' => FulfillmentStatus::Arrived], ['status' => OrderStatus::Arrived]);

    $this->actingAs($other, 'driver')
        ->post(route('driver.orders.delivery-code', $order))
        ->assertNotFound();

    expect(app(FakeSmsSender::class)->messages)->toBeEmpty();
});

test('a fourth delivery code within ten minutes is refused', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Arrived], ['status' => OrderStatus::Arrived]);

    foreach (range(1, OrderPlacementNotifier::RESEND_LIMIT) as $ignored) {
        $this->actingAs($driver, 'driver')
            ->post(route('driver.orders.delivery-code', $order))
            ->assertSessionHasNoErrors();
    }

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.delivery-code', $order))
        ->assertSessionHasErrors('code');

    expect(app(FakeSmsSender::class)->messages)->toHaveCount(OrderPlacementNotifier::RESEND_LIMIT);
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
