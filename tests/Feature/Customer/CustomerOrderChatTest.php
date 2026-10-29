<?php

use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Support\ConversationUnreadCounts;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{0: User, 1: User, 2: Order}
 */
function customerChatOrder(FulfillmentStatus $status = FulfillmentStatus::OutForDelivery, bool $openThread = true): array
{
    $customer = User::factory()->create();
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
        'name' => 'Sam Driver',
    ]);
    $order = driverPortalOrder($driver, ['status' => $status], ['customer_id' => $customer->id]);

    if ($openThread) {
        app(ConversationService::class)->openForOrder($order->fresh('fulfillment'));
    }

    return [$customer, $driver, $order->refresh()];
}

test('customer can open the driver chat once the order is on the way', function () {
    [$customer, , $order] = customerChatOrder();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.chat', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/chat')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.driverName', 'Sam Driver')
            ->where('viewerId', $customer->id)
            ->where('canSend', true)
            ->where('channel', $order->conversation->channelName())
            ->has('messages'));
});

test('customer order detail exposes chat while the order is on the way', function () {
    [$customer, , $order] = customerChatOrder();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.canChat', true));
});

test('customer order list flags orders with an open driver chat', function () {
    [$customer, $driver, $onTheWay] = customerChatOrder();
    $assigned = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned], [
        'customer_id' => $customer->id,
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/index')
            ->has('orders', 2)
            ->where('orders.0.orderNumber', $onTheWay->order_number)
            ->where('orders.0.canChat', true)
            ->where('orders.1.orderNumber', $assigned->order_number)
            ->where('orders.1.canChat', false));
});

test('customers cannot open another customers order chat', function () {
    [, , $order] = customerChatOrder();
    $other = User::factory()->create();

    $this->actingAs($other, 'customer')
        ->get(route('customer.orders.chat', $order))
        ->assertNotFound();
});

test('order chat is unavailable before the thread opens', function () {
    [$customer, , $order] = customerChatOrder(FulfillmentStatus::Assigned, openThread: false);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.chat', $order))
        ->assertNotFound();
});

test('customer can reply to the driver', function () {
    [$customer, , $order] = customerChatOrder();

    $this->actingAs($customer, 'customer')
        ->from(route('customer.orders.chat', $order))
        ->post(route('customer.orders.messages.store', $order), ['body' => 'Please leave it at the door.'])
        ->assertRedirect(route('customer.orders.chat', $order));

    expect(Message::query()
        ->where('conversation_id', $order->conversation->id)
        ->where('sender_id', $customer->id)
        ->where('body', 'Please leave it at the door.')
        ->exists())->toBeTrue();
});

test('delivered order chat is read-only for the customer', function () {
    [$customer, , $order] = customerChatOrder(FulfillmentStatus::Delivered);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.chat', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canSend', false));

    $this->actingAs($customer, 'customer')
        ->post(route('customer.orders.messages.store', $order), ['body' => 'Thanks!'])
        ->assertNotFound();
});

test('customer chat page exposes receipts and delivery ack urls', function () {
    [$customer, , $order] = customerChatOrder();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.chat', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('receipts')
            ->has('deliveredUrl')
            ->has('readUrl')
            ->has('viewerLastReadMessageId'));
});

test('customer can acknowledge delivery and read on order chat', function () {
    [$customer, $driver, $order] = customerChatOrder();
    $conversation = $order->conversation;
    $message = app(ConversationService::class)
        ->sendMessage($conversation, $driver, 'Almost there.');

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.orders.chat.delivered', $order), [
            'message_id' => $message->id,
        ])
        ->assertOk();

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.orders.chat.read', $order), [
            'message_id' => $message->id,
        ])
        ->assertOk();

    $participant = $conversation->participants()->where('user_id', $customer->id)->firstOrFail();

    expect($participant->last_delivered_message_id)->toBe($message->id)
        ->and($participant->last_read_message_id)->toBe($message->id);
});

test('customer order list and detail expose unread chat counts', function () {
    [$customer, $driver, $order] = customerChatOrder();
    $conversation = $order->conversation;
    app(ConversationService::class)->sendMessage($conversation, $driver, 'Hello from the road.');
    ConversationUnreadCounts::forget($customer->id);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'orders.0.chatUnreadCount',
                fn ($count): bool => is_int($count) && $count >= 1,
            ));

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'order.chatUnreadCount',
                fn ($count): bool => is_int($count) && $count >= 1,
            ));
});
