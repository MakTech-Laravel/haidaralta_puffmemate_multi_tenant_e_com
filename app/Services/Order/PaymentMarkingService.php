<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PaymentMarkingService
{
    public function collect(Order $order, User $collector): Payment
    {
        $payment = $this->paymentFor($order);

        if (in_array($payment->status, [PaymentStatus::Collected, PaymentStatus::Verified], true)) {
            return $payment;
        }

        if ($payment->status !== PaymentStatus::PendingCollection) {
            throw ValidationException::withMessages([
                'payment_status' => 'This payment cannot be collected.',
            ]);
        }

        $payment->forceFill([
            'status' => PaymentStatus::Collected,
            'collected_by' => $collector->id,
            'collected_at' => now(),
            'paid_at' => now(),
        ])->save();

        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'event_type' => 'collected',
            'response' => ['collector_id' => $collector->id],
        ]);

        $this->syncOrder($order, $payment);

        return $payment->refresh();
    }

    public function verify(Order $order, User $verifier): Payment
    {
        $payment = $this->paymentFor($order);

        if ($payment->status === PaymentStatus::Verified) {
            return $payment;
        }

        if ($payment->status !== PaymentStatus::Collected) {
            throw ValidationException::withMessages([
                'payment_status' => 'Collect the payment before verifying it.',
            ]);
        }

        $payment->forceFill([
            'status' => PaymentStatus::Verified,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ])->save();

        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'event_type' => 'verified',
            'response' => ['verifier_id' => $verifier->id],
        ]);

        $this->syncOrder($order, $payment);

        return $payment->refresh();
    }

    public function fail(Order $order): Payment
    {
        $payment = $this->paymentFor($order);

        if ($payment->status !== PaymentStatus::PendingCollection) {
            return $payment;
        }

        $payment->forceFill(['status' => PaymentStatus::Failed])->save();
        $this->syncOrder($order, $payment);

        return $payment->refresh();
    }

    public function markFulfillmentPaid(OrderFulfillment $fulfillment): OrderFulfillment
    {
        if ($fulfillment->isPaid()) {
            return $fulfillment;
        }

        $order = $fulfillment->order()->first();
        $collector = auth()->user();

        if ($collector instanceof User) {
            $this->collect($order, $collector);
        }

        $fulfillment->forceFill(['paid_at' => now()])->save();

        return $fulfillment->refresh();
    }

    public function overrideOrderPaymentStatus(Order $order, PaymentStatus $status): Order
    {
        $payment = $this->paymentFor($order);
        $actor = auth()->user();

        if ($status === PaymentStatus::Collected && $actor instanceof User) {
            $this->collect($order, $actor);
        } elseif ($status === PaymentStatus::Verified && $actor instanceof User) {
            $this->verify($order, $actor);
        } elseif ($status === PaymentStatus::Failed) {
            $this->fail($order);
        } elseif ($status === PaymentStatus::Refunded && $actor instanceof User) {
            $this->refund($order, $actor);
        } else {
            throw ValidationException::withMessages([
                'payment_status' => 'This payment status cannot be set directly.',
            ]);
        }

        return $order->refresh();
    }

    public function refund(Order $order, User $actor): Payment
    {
        $payment = $this->paymentFor($order);

        if (! in_array($payment->status, [PaymentStatus::Collected, PaymentStatus::Verified], true)) {
            throw ValidationException::withMessages([
                'payment_status' => 'Collect or verify the payment before refunding it.',
            ]);
        }

        if ($order->fulfillment?->status !== FulfillmentStatus::Delivered) {
            throw ValidationException::withMessages([
                'payment_status' => 'Refund is only available after the order is delivered.',
            ]);
        }

        $payment->forceFill(['status' => PaymentStatus::Refunded])->save();

        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'event_type' => 'refunded',
            'response' => ['actor_id' => $actor->id],
        ]);

        $this->syncOrder($order, $payment->refresh());

        return $payment->refresh();
    }

    private function paymentFor(Order $order): Payment
    {
        $payment = $order->payment()->first() ?? $order->payments()->first();

        if ($payment === null) {
            throw ValidationException::withMessages([
                'payment_status' => 'This order has no payment record.',
            ]);
        }

        return $payment;
    }

    private function syncOrder(Order $order, Payment $payment): void
    {
        $order->forceFill(['payment_status' => $payment->status])->save();

        if (in_array($payment->status, [PaymentStatus::Collected, PaymentStatus::Verified], true)) {
            $order->fulfillment?->forceFill(['paid_at' => $payment->paid_at ?? now()])->save();
        }
    }
}
