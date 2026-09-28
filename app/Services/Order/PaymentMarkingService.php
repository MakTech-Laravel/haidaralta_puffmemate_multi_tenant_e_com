<?php

namespace App\Services\Order;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;

/**
 * Phase 1 payment collection: branch staff mark their own fulfillment's cash
 * or POS payment as collected; once every fulfillment on an order is marked
 * paid, the master order's payment_status flips to Paid. Platform admin can
 * override the master payment_status directly at any time.
 *
 * A future driver-workflow phase narrows fulfillment-level marking to the
 * assigned driver — only the authorization check changes, this boundary
 * stays the same.
 */
class PaymentMarkingService
{
    public function markFulfillmentPaid(OrderFulfillment $fulfillment): OrderFulfillment
    {
        if ($fulfillment->isPaid()) {
            return $fulfillment;
        }

        $fulfillment->forceFill(['paid_at' => now()])->save();

        $order = $fulfillment->order()->first();
        $order->loadMissing('fulfillments');

        if ($order->fulfillments->every(fn (OrderFulfillment $row): bool => $row->isPaid())) {
            $order->forceFill(['payment_status' => PaymentStatus::Paid])->save();
        }

        return $fulfillment->refresh();
    }

    public function overrideOrderPaymentStatus(Order $order, PaymentStatus $status): Order
    {
        $order->forceFill(['payment_status' => $status])->save();

        return $order->refresh();
    }
}
