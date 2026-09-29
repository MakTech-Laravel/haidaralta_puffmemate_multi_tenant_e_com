<?php

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Order\PaymentMarkingService;
use Illuminate\Validation\ValidationException;

test('collecting payment twice stays collected without a second ledger event', function () {
    $collector = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $service = app(PaymentMarkingService::class);
    $first = $service->collect($order, $collector);
    $second = $service->collect($order->refresh(), $collector);

    expect($first->status)->toBe(PaymentStatus::Collected)
        ->and($second->status)->toBe(PaymentStatus::Collected)
        ->and($order->refresh()->payment_status)->toBe(PaymentStatus::Collected)
        ->and(PaymentTransaction::query()->where('event_type', 'collected')->count())->toBe(1);
});

test('verifying before collecting is rejected', function () {
    $verifier = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $service = app(PaymentMarkingService::class);

    expect(fn () => $service->verify($order, $verifier))->toThrow(ValidationException::class);
});

test('verifying twice stays verified without a second ledger event', function () {
    $collector = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $service = app(PaymentMarkingService::class);
    $service->collect($order, $collector);
    $first = $service->verify($order->refresh(), $collector);
    $second = $service->verify($order->refresh(), $collector);

    expect($first->status)->toBe(PaymentStatus::Verified)
        ->and($second->status)->toBe(PaymentStatus::Verified)
        ->and(PaymentTransaction::query()->where('event_type', 'verified')->count())->toBe(1);
});

test('the platform override rejects verifying an uncollected payment instead of auto collecting it', function () {
    $actor = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $service = app(PaymentMarkingService::class);
    $this->actingAs($actor);

    expect(fn () => $service->overrideOrderPaymentStatus($order, PaymentStatus::Verified))
        ->toThrow(ValidationException::class);

    expect(Payment::query()->where('order_id', $order->id)->first()->status)
        ->toBe(PaymentStatus::PendingCollection);
});

test('refund is rejected before the order is delivered', function () {
    $actor = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Collected]);
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::OutForDelivery]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Collected]);

    $service = app(PaymentMarkingService::class);

    expect(fn () => $service->refund($order, $actor))->toThrow(ValidationException::class);
});

test('refund is rejected before the payment is collected', function () {
    $actor = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Delivered]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $service = app(PaymentMarkingService::class);

    expect(fn () => $service->refund($order, $actor))->toThrow(ValidationException::class);
});

test('a delivered order with a collected payment can be refunded', function () {
    $actor = User::factory()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Collected]);
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Delivered]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Collected]);

    $service = app(PaymentMarkingService::class);
    $payment = $service->refund($order, $actor);

    expect($payment->status)->toBe(PaymentStatus::Refunded)
        ->and($order->refresh()->payment_status)->toBe(PaymentStatus::Refunded)
        ->and(PaymentTransaction::query()->where('event_type', 'refunded')->count())->toBe(1);
});
