<?php

namespace App\Services\Order;

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
            if ($payment->status === PaymentStatus::PendingCollection) {
                $this->collect($order, $actor);
            }

            $this->verify($order->refresh(), $actor);
        } elseif ($status === PaymentStatus::Failed) {
            $this->fail($order);
        } else {
            $payment->forceFill(['status' => $status])->save();
            $this->syncOrder($order, $payment->refresh());
        }

        return $order->refresh();
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
