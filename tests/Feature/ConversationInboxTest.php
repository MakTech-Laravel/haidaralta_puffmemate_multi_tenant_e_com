<?php

use App\Enums\ConversationParticipantRole;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Support\ConversationUnreadCounts;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * A thread on the given branch with the operator joined and `$unread` messages
 * they have not seen.
 */
function seedThread(Branch $branch, User $operator, int $unread = 0): Conversation
{
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    $order = Order::factory()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
    ]);

    $conversation = Conversation::factory()->forOrder($order)->create([
        'last_message_preview' => 'Where is my order?',
        'last_message_at' => now(),
    ]);

    ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $operator->id,
        'role' => ConversationParticipantRole::BranchStaff,
    ]);

    ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $customer->id,
        'role' => ConversationParticipantRole::Customer,
    ]);

    Message::factory()->count($unread)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $customer->id,
    ]);

    return $conversation;
}

test('the platform inbox renders the workspace with conversation rows', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $conversation = seedThread($sydney, $admin);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.conversations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/conversations/index')
            ->where('thread', null)
            ->has('inboxMeta.statuses')
            ->has('conversations', 1)
            ->where('conversations.0.id', $conversation->id)
            ->where('conversations.0.last_message_preview', 'Where is my order?'));
});

test('opening a thread renders the same component with the transcript attached', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $conversation = seedThread($sydney, $admin, unread: 3);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.conversations.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // Same component as the index, so the list never remounts.
            ->component('platform/conversations/index')
            ->where('thread.conversation.id', $conversation->id)
            ->has('thread.messages', 3)
            ->has('thread.endpoints.send')
            ->has('conversations'));
});

test('the branch inbox only lists that branch and hides other branches threads', function () {
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $mine = seedThread($sydney, $operator);
    $theirs = seedThread($melbourne, $operator);

    $this->actingAs($operator, 'branch')
        ->get(route('branch.conversations.index', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/conversations/index')
            ->has('conversations', 1)
            ->where('conversations.0.id', $mine->id));

    // The foreign thread does not exist for this portal, so it is a 404.
    $this->actingAs($operator, 'branch')
        ->get(route('branch.conversations.show', [$sydney, $theirs]))
        ->assertNotFound();
});

test('the status filter narrows the inbox to closed threads', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    seedThread($sydney, $admin);
    $closed = seedThread($sydney, $admin);
    $closed->forceFill(['status' => 'closed', 'closed_at' => now()])->save();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.conversations.index', ['status' => 'closed']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversations', 1)
            ->where('conversations.0.id', $closed->id));
});

test('unread counts exclude the viewers own messages and are cached per user', function () {
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $conversation = seedThread($sydney, $operator, unread: 4);

    Message::factory()->count(2)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $operator->id,
    ]);

    expect(ConversationUnreadCounts::forUser($operator))
        ->toBe([$conversation->id => 4]);

    // A later message is invisible until the cache is busted, proving the
    // aggregate is not re-run on every inbox render.
    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => User::query()->where('email', UserSeeder::CUSTOMER)->value('id'),
    ]);

    expect(ConversationUnreadCounts::forUser($operator))
        ->toBe([$conversation->id => 4]);

    ConversationUnreadCounts::forget($operator->id);

    expect(ConversationUnreadCounts::forUser($operator))
        ->toBe([$conversation->id => 5]);
});

test('the platform branch filter narrows the inbox to one branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $inSydney = seedThread($sydney, $admin);
    seedThread($melbourne, $admin);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.conversations.index', ['branch_id' => $sydney->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversations', 1)
            ->where('conversations.0.id', $inSydney->id)
            // Only the label travels in the props; the combobox pages the
            // rest of the options itself.
            ->where('inboxMeta.branchLabel', $sydney->name));
});

test('the branch options endpoint pages and searches branches that have threads', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    seedThread($sydney, $admin);
    seedThread($melbourne, $admin);

    // Brisbane has no thread, so it must not be offered as a filter.
    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.conversations.branch-options'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.last_page', 1);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.conversations.branch-options', ['filter' => ['search' => 'Melbourne']]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $melbourne->id);

    // One row per page proves the combobox has something to scroll for.
    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.conversations.branch-options', ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10);
});

test('the branch portal has no branch filter label and no options route', function () {
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    seedThread($sydney, $operator);

    $this->actingAs($operator, 'branch')
        ->get(route('branch.conversations.index', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('inboxMeta.branchLabel', null));
});

test('a branch operator cannot widen their scope with the branch filter', function () {
    $operator = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $mine = seedThread($sydney, $operator);
    seedThread($melbourne, $operator);

    $this->actingAs($operator, 'branch')
        ->get(route('branch.conversations.index', [$sydney, 'branch_id' => $melbourne->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversations', 1)
            ->where('conversations.0.id', $mine->id));
});

test('opening a conversation for an order that already has one is rejected', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $conversation = seedThread($sydney, $admin);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.conversations.index'))
        ->post(route('platform.conversations.store'), [
            'order_id' => $conversation->order_id,
        ])
        ->assertSessionHasErrors('order_id');

    expect(Conversation::query()->count())->toBe(1);
});
