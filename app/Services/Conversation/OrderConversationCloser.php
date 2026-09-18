<?php

namespace App\Services\Conversation;

use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * Keeps order status and conversation status in step.
 *
 * Checkout does not exist yet, so this is the single entry point for moving an
 * order forward. When the real order lifecycle lands it should call through here
 * rather than writing `orders.status` directly.
 */
class OrderConversationCloser
{
    public function __construct(private ConversationService $conversations) {}

    public function transition(Order $order, OrderStatus $status): Order
    {
        $order->forceFill(['status' => $status])->save();

        if ($status->isTerminal()) {
            $this->conversations->closeForOrder($order->refresh());
        }

        return $order;
    }
}
