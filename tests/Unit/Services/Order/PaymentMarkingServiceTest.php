<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Order\PaymentMarkingService;

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
