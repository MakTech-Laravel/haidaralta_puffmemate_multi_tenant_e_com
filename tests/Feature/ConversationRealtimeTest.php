<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Events\ConversationClosed;
use App\Events\ConversationInboxUpdated;
use App\Events\MessageCreated;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\OrderConversationCloser;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\OrderConversationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function openThread(): Conversation
{
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $order = Order::factory()->create([
        'branch_id' => $sydney->id,
        'customer_id' => $customer->id,
    ]);

    return app(ConversationService::class)->openForOrder($order);
}

test('sending a message broadcasts to the thread and to every participant inbox', function () {
    Event::fake([MessageCreated::class, ConversationInboxUpdated::class]);

    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $conversation = openThread();

    app(ConversationService::class)->sendMessage($conversation, $operator, 'The driver is five minutes away.');

    Event::assertDispatched(
        MessageCreated::class,
        fn (MessageCreated $event) => $event->message->body === 'The driver is five minutes away.',
    );

    Event::assertDispatched(
        ConversationInboxUpdated::class,
        fn (ConversationInboxUpdated $event) => in_array($operator->id, $event->userIds, true),
    );
});

test('a sent message updates the denormalized inbox preview', function () {
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $conversation = openThread();

    app(ConversationService::class)->sendMessage($conversation, $operator, 'Reissuing the invoice now.');

    expect($conversation->refresh()->last_message_preview)
        ->toBe('Reissuing the invoice now.')
        ->and($conversation->last_message_sender_id)->toBe($operator->id)
        ->and($conversation->last_message_at)->not->toBeNull();
});

test('an attachment-only message still produces a readable preview', function () {
    Storage::fake('local');

    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $conversation = openThread();

    app(ConversationService::class)->sendMessage($conversation, $operator, null, [
        UploadedFile::fake()->image('receipt.jpg'),
    ]);

    expect($conversation->refresh()->last_message_preview)->toBe('1 attachment');
});

test('a terminal order status closes the thread and broadcasts the closure', function () {
    Event::fake([ConversationClosed::class]);

    $conversation = openThread();

    app(OrderConversationCloser::class)->transition(
        $conversation->order,
        OrderStatus::Delivered,
    );

    expect($conversation->refresh()->isClosed())->toBeTrue();

    Event::assertDispatched(
        ConversationClosed::class,
        fn (ConversationClosed $event) => $event->conversation->id === $conversation->id,
    );
});

test('a closed thread rejects new messages', function () {
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $conversation = openThread();

    app(ConversationService::class)->close($conversation, 'Closed for testing.');

    expect(fn () => app(ConversationService::class)->sendMessage($conversation, $operator, 'Still there?'))
        ->toThrow(RuntimeException::class);
});

/**
 * The test broadcaster authorizes everything, so channel-auth assertions need
 * the real Reverb broadcaster wired up first.
 *
 * `routes/channels.php` is not loaded in the test environment, so it is
 * required here rather than duplicated — the assertions then cover the real
 * channel definitions.
 */
function useReverbForConversationAuth(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-id',
        'broadcasting.connections.reverb.options.host' => 'localhost',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
        'broadcasting.connections.reverb.options.useTLS' => true,
    ]);

    // Must come after the swap: channels register on the resolved broadcaster.
    require base_path('routes/channels.php');
}

test('the presence channel authorizes operators and rejects staff without the permission', function () {
    useReverbForConversationAuth();

    $conversation = openThread();
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $outsider = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $outsider->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'presence-conversation.'.$conversation->id,
        ])
        ->assertOk();

    $this->actingAs($outsider, 'platform')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'presence-conversation.'.$conversation->id,
        ])
        ->assertForbidden();
});

test('an inbox channel only authorizes its own user', function () {
    useReverbForConversationAuth();

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $other = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversation-inbox.'.$admin->id,
        ])
        ->assertOk();

    $this->actingAs($admin, 'platform')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversation-inbox.'.$other->id,
        ])
        ->assertForbidden();
});

test('the order conversation seeder produces readable threads', function () {
    $this->seed(OrderConversationSeeder::class);

    $conversation = Conversation::query()
        ->whereRelation('order', 'order_number', 'PMM-2026-1041')
        ->firstOrFail();

    expect($conversation->messages()->count())->toBe(5)
        ->and($conversation->last_message_preview)
        ->toBe('On my way, about 15 minutes out. I will call when I am downstairs.')
        ->and($conversation->participants()->count())->toBe(3);
});
