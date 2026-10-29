<?php

use App\Enums\ConversationParticipantRole;
use App\Enums\ConversationType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function platformBranchChatBranch(): Branch
{
    return Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
}

function platformBranchChatPlatformStaff(): User
{
    $user = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    app(PermissionRegistrar::class)->setPermissionsTeamId(
        PlatformTeamResolver::PLATFORM_TEAM_ID,
    );

    return $user;
}

function platformBranchChatBranchStaff(): User
{
    $user = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);

    return $user;
}

test('platform staff can open a shared branch chat without order customer or driver', function () {
    $branch = platformBranchChatBranch();
    $staff = platformBranchChatPlatformStaff();

    $this->actingAs($staff, 'platform')
        ->post(route('platform.branch-chats.store'), [
            'branch_id' => $branch->id,
        ])
        ->assertRedirect();

    $conversation = Conversation::query()
        ->where('type', ConversationType::PlatformBranch)
        ->where('branch_id', $branch->id)
        ->sole();

    expect($conversation->order_id)->toBeNull()
        ->and($conversation->driver_id)->toBeNull()
        ->and($conversation->participants()->where('role', ConversationParticipantRole::Customer)->exists())->toBeFalse()
        ->and($conversation->participants()->where('role', ConversationParticipantRole::Driver)->exists())->toBeFalse()
        ->and($conversation->participants()->where('user_id', $staff->id)->exists())->toBeTrue();
});

test('opening the same branch twice returns the same conversation', function () {
    $branch = platformBranchChatBranch();
    $staff = platformBranchChatPlatformStaff();
    $service = app(ConversationService::class);

    $first = $service->openPlatformBranch($branch, $staff);
    $second = $service->openPlatformBranch($branch, $staff);

    expect($second->id)->toBe($first->id);
});

test('staff without platform branch chat permission cannot open the inbox', function () {
    $staff = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($staff, 'platform')
        ->get(route('platform.branch-chats.index'))
        ->assertForbidden();
});

test('branch staff without permission cannot open the inbox', function () {
    $branch = platformBranchChatBranch();
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.platform-chats.index', $branch))
        ->assertForbidden();
});

test('branch staff can start the same shared thread and second platform staff joins it', function () {
    $branch = platformBranchChatBranch();
    $branchStaff = platformBranchChatBranchStaff();
    $platformStaff = platformBranchChatPlatformStaff();
    $service = app(ConversationService::class);

    $this->actingAs($branchStaff, 'branch')
        ->post(route('branch.platform-chats.store', $branch))
        ->assertRedirect();

    $conversation = Conversation::query()
        ->where('type', ConversationType::PlatformBranch)
        ->where('branch_id', $branch->id)
        ->sole();

    $second = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    app(PermissionRegistrar::class)->setPermissionsTeamId(
        PlatformTeamResolver::PLATFORM_TEAM_ID,
    );

    $this->actingAs($second, 'platform')
        ->get(route('platform.branch-chats.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/branch-chats/index')
            ->where('thread.conversation.id', $conversation->id)
            ->where('thread.conversation.type', 'platform_branch'));

    expect(ConversationParticipant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $second->id)
        ->exists())->toBeTrue();

    $reopened = $service->openPlatformBranch($branch, $platformStaff);
    expect($reopened->id)->toBe($conversation->id);
});

test('both portals include sender role labels on messages', function () {
    $branch = platformBranchChatBranch();
    $platformStaff = platformBranchChatPlatformStaff();
    $branchStaff = platformBranchChatBranchStaff();
    $service = app(ConversationService::class);

    $conversation = $service->openPlatformBranch($branch, $platformStaff);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    $this->actingAs($platformStaff, 'platform')
        ->post(route('platform.branch-chats.messages.store', $conversation), [
            'body' => 'HQ needs a stock check',
        ])
        ->assertCreated()
        ->assertJsonPath('message.sender.role_label', fn ($label) => is_string($label) && $label !== '');

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

    $this->actingAs($branchStaff, 'branch')
        ->post(route('branch.platform-chats.messages.store', [$branch, $conversation]), [
            'body' => 'On it from the branch',
        ])
        ->assertCreated()
        ->assertJsonPath('message.sender.role_label', fn ($label) => is_string($label) && $label !== '');
});

test('customer and driver cannot access platform branch chat', function () {
    $branch = platformBranchChatBranch();
    $staff = platformBranchChatPlatformStaff();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $service = app(ConversationService::class);

    $conversation = $service->openPlatformBranch($branch, $staff);

    $this->actingAs($customer, 'web')
        ->get(route('platform.branch-chats.show', $conversation))
        ->assertRedirect();

    $this->actingAs($driver, 'driver')
        ->get(route('platform.branch-chats.show', $conversation))
        ->assertRedirect();
});
