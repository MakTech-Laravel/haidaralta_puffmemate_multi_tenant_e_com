<?php

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Mail\OrderPlacementCodeMail;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Sms\FakeSmsSender;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

test('placing an order sends one code by sms, email, and notification', function () {
    Mail::fake();

    $customer = User::factory()->create(['email' => 'ada@example.com']);
    $order = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Placed,
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '+61410111222',
            'address' => '1 Test Street',
        ],
    ]);

    app(OrderPlacementNotifier::class)->issue($order);

    $code = app(FakeSmsSender::class)->lastCode();

    expect($code)->not->toBeNull();

    Mail::assertSent(OrderPlacementCodeMail::class, function (OrderPlacementCodeMail $mail) use ($customer, $code): bool {
        return $mail->hasTo($customer->email) && $mail->code === $code;
    });

    expect(UserNotification::query()->where('user_id', $customer->id)->count())->toBe(1);
});

test('a pending order does not receive a placement code', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Pending,
        'shipping_address_snapshot' => ['phone' => '+61410111222'],
    ]);

    expect(fn () => app(OrderPlacementNotifier::class)->issue($order))
        ->toThrow(ValidationException::class);

    expect(app(FakeSmsSender::class)->lastCode())->toBeNull();
});

function placedOrderWithCode(User $customer, FulfillmentStatus $status = FulfillmentStatus::Placed): Order
{
    $order = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::from($status->value),
        'shipping_address_snapshot' => ['phone' => '+61410111222', 'name' => 'Ada'],
    ]);
    OrderFulfillment::factory()->forOrder($order)->create(['status' => $status]);
    app(OrderPlacementNotifier::class)->issue($order);

    return $order;
}

test('the customer sees their delivery code on the order page', function () {
    $customer = User::factory()->create();
    $order = placedOrderWithCode($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.deliveryCode', app(FakeSmsSender::class)->lastCode()));
});

test('the delivery code is hidden once the order is delivered', function () {
    $customer = User::factory()->create();
    $order = placedOrderWithCode($customer);
    $order->forceFill(['status' => OrderStatus::Delivered])->save();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('order.deliveryCode', null));
});

test('cancelling an order discards its delivery code', function () {
    $customer = User::factory()->create();
    $order = placedOrderWithCode($customer);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.orders.cancel', $order))
        ->assertRedirect();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(app(OrderPlacementNotifier::class)->currentCode($order))->toBeNull();
});

test('the customer can resend the placement code', function () {
    $customer = User::factory()->create(['email' => 'ada@example.com']);
    $order = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Placed,
        'order_number' => 'PMM-CODE-1',
        'shipping_address_snapshot' => ['phone' => '+61410111222', 'name' => 'Ada'],
    ]);
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Placed]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.orders.placement-code', $order))
        ->assertRedirect();

    expect(app(FakeSmsSender::class)->lastCode())->not->toBeNull()
        ->and(UserNotification::query()->where('user_id', $customer->id)->whereHas('notification', fn ($query) => $query->where('type', NotificationType::Order))->count())->toBeGreaterThan(0);
});
