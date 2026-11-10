<?php

namespace App\Support\Order;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * The public, login-free link a guest uses to follow an order. It carries a
 * signature, so the order number alone never opens the page.
 */
final class OrderTrackingLink
{
    /** Days the link keeps working after the order is completed (delivered or cancelled). */
    public const VALID_DAYS_AFTER_COMPLETION = 30;

    public static function url(Order $order): string
    {
        return URL::signedRoute('orders.track', ['order' => $order->order_number]);
    }

    /**
     * An order still in progress is never expired; a completed one expires
     * VALID_DAYS_AFTER_COMPLETION days after it was completed.
     */
    public static function isExpired(Order $order): bool
    {
        if (! $order->status->isTerminal()) {
            return false;
        }

        $completedAt = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('new_status', $order->status->value)
            ->latest('id')
            ->value('created_at');

        $completedAt = $completedAt === null
            ? $order->updated_at
            : Carbon::parse($completedAt);

        return $completedAt->copy()->addDays(self::VALID_DAYS_AFTER_COMPLETION)->isPast();
    }
}
