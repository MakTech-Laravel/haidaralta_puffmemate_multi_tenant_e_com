<?php

use App\Events\ConversationReceiptsUpdated;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\ConversationVolumeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * An open thread on Sydney CBD with the customer and driver joined.
 */
function receiptThread(): Conversation
{
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
    ]);

    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'driver_id' => $driver->id,
    ]);

    return app(ConversationService::class)->openForOrder($order);
}

test('acknowledging delivery moves only the delivered marker', function () {
    $conversation = receiptThread();
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $message = app(ConversationService::class)
        ->sendMessage($conversation, $operator, 'Your driver is on the way.');

    app(ConversationService::class)->markDelivered($conversation, $customer, $message->id);

    $participant = $conversation->participants()->where('user_id', $customer->id)->firstOrFail();

    expect($participant->last_delivered_message_id)->toBe($message->id)
        ->and($participant->last_read_message_id)->toBeNull();
});

test('reading drags the delivered marker along, because a read message arrived', function () {
    $conversation = receiptThread();
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $message = app(ConversationService::class)
        ->sendMessage($conversation, $operator, 'Your driver is on the way.');

    app(ConversationService::class)->markRead($conversation, $customer, $message->id);

    $participant = $conversation->participants()->where('user_id', $customer->id)->firstOrFail();

    expect($participant->last_delivered_message_id)->toBe($message->id)
        ->and($participant->last_read_message_id)->toBe($message->id);
});

test('markers never move backwards', function () {
    $conversation = receiptThread();
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $first = app(ConversationService::class)->sendMessage($conversation, $operator, 'First.');
    $second = app(ConversationService::class)->sendMessage($conversation, $operator, 'Second.');

    app(ConversationService::class)->markRead($conversation, $customer, $second->id);
    app(ConversationService::class)->markRead($conversation, $customer, $first->id);

    expect($conversation->participants()->where('user_id', $customer->id)->value('last_read_message_id'))
        ->toBe($second->id);
});

test('a marker that actually moves broadcasts the whole receipt set', function () {
    $conversation = receiptThread();
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $message = app(ConversationService::class)
        ->sendMessage($conversation, $operator, 'Your driver is on the way.');

    Event::fake([ConversationReceiptsUpdated::class]);

    app(ConversationService::class)->markDelivered($conversation, $customer, $message->id);

    Event::assertDispatched(
        ConversationReceiptsUpdated::class,
        function (ConversationReceiptsUpdated $event) use ($customer, $message): bool {
            $mine = collect($event->broadcastWith()['receipts'])
                ->firstWhere('user_id', $customer->id);

            return $mine !== null
                && $mine['last_delivered_message_id'] === $message->id;
        },
    );
});

test('a no-op acknowledgement does not broadcast', function () {
    $conversation = receiptThread();
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $message = app(ConversationService::class)
        ->sendMessage($conversation, $operator, 'Your driver is on the way.');

    app(ConversationService::class)->markDelivered($conversation, $customer, $message->id);

    Event::fake([ConversationReceiptsUpdated::class]);

    app(ConversationService::class)->markDelivered($conversation, $customer, $message->id);

    Event::assertNotDispatched(ConversationReceiptsUpdated::class);
});

test('the thread payload carries the receipt markers the ticks are derived from', function () {
    $conversation = receiptThread();
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.conversations.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // One marker pair per participant: the ticks are derived from
            // these client-side, so a missing field downgrades a message.
            ->has(
                'thread.conversation.receipts',
                $conversation->participants()->count(),
                fn ($receipt) => $receipt
                    ->has('user_id')
                    ->has('last_delivered_message_id')
                    ->has('last_read_message_id'),
            )
            ->has('thread.endpoints.delivered')
            ->has('thread.viewerLastReadMessageId'));
});

test('the delivered endpoint rejects an operator from another branch', function () {
    $conversation = receiptThread();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $operator = User::query()->where('email', UserSeeder::BRANCH_2_ADMIN)->firstOrFail();

    $this->actingAs($operator, 'branch')
        ->postJson(route('branch.conversations.delivered', [$melbourne, $conversation]), [
            'message_id' => 1,
        ])
        ->assertNotFound();
});

test('the volume seeder produces enough data to page both scrollers', function () {
    $this->seed(ConversationVolumeSeeder::class);

    $deepest = Conversation::query()
        ->withCount('messages')
        ->orderByDesc('messages_count')
        ->firstOrFail();

    $spareOrders = Order::query()->whereDoesntHave('conversation')->count();

    expect(Conversation::query()->count())->toBeGreaterThan(50)
        ->and($deepest->messages_count)->toBeGreaterThan(500)
        // The New conversation picker needs something to pick.
        ->and($spareOrders)->toBeGreaterThan(0);
});
