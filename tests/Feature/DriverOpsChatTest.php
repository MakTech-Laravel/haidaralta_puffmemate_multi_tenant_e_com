<?php

use App\Enums\ConversationParticipantRole;
use App\Enums\ConversationType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Services\Conversation\ConversationService;
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

function driverOpsBranch(): Branch
{
    return Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
}

function driverOpsStaff(): User
{
    $user = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);

    return $user;
}

function driverOpsDriver(): User
{
    return User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
}

test('branch staff can open a shared driver ops chat without a customer or order', function () {
    $branch = driverOpsBranch();
    $staff = driverOpsStaff();
    $driver = driverOpsDriver();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.driver-chats.store', $branch), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect();

    $conversation = Conversation::query()
        ->where('type', ConversationType::DriverOps)
        ->where('branch_id', $branch->id)
        ->where('driver_id', $driver->id)
        ->sole();

    expect($conversation->order_id)->toBeNull()
        ->and($conversation->participants()->where('role', ConversationParticipantRole::Customer)->exists())->toBeFalse()
        ->and($conversation->participants()->where('user_id', $driver->id)->exists())->toBeTrue();
});

test('opening the same branch and driver twice returns the same conversation', function () {
    $branch = driverOpsBranch();
    $staff = driverOpsStaff();
    $driver = driverOpsDriver();
    $service = app(ConversationService::class);

    $first = $service->openDriverOps($branch, $driver, $staff);
    $second = $service->openDriverOps($branch, $driver, $staff);

    expect($second->id)->toBe($first->id);
});

test('staff without driver chat permission cannot open the inbox', function () {
    $branch = driverOpsBranch();
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.driver-chats.index', $branch))
        ->assertForbidden();
});

test('second staff with permission joins the same shared thread', function () {
    $branch = driverOpsBranch();
    $admin = driverOpsStaff();
    $driver = driverOpsDriver();
    $service = app(ConversationService::class);

    $conversation = $service->openDriverOps($branch, $driver, $admin);

    $other = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

    $this->actingAs($other, 'branch')
        ->get(route('branch.driver-chats.show', [$branch, $conversation]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/driver-chats/index')
            ->where('thread.conversation.id', $conversation->id)
            ->where('thread.conversation.type', 'driver_ops'));

    expect(ConversationParticipant::query()
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $other->id)
        ->exists())->toBeTrue();
});

test('driver can start and message a branch chat; other driver cannot view it', function () {
    $branch = driverOpsBranch();
    $driver = driverOpsDriver();
    $other = User::query()->where('email', '!=', UserSeeder::DRIVER)
        ->where('user_type', UserType::DRIVER)
        ->firstOrFail();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.chats.store'), ['branch_id' => $branch->id])
        ->assertRedirect();

    $conversation = Conversation::query()
        ->where('type', ConversationType::DriverOps)
        ->where('driver_id', $driver->id)
        ->where('branch_id', $branch->id)
        ->sole();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.chats.messages.store', $conversation), [
            'body' => 'Hello branch',
        ])
        ->assertCreated()
        ->assertJsonMissingPath('message.sender.role_label');

    $this->actingAs($other, 'driver')
        ->get(route('driver.chats.show', $conversation))
        ->assertForbidden();
});

test('branch message payload includes sender role label', function () {
    $branch = driverOpsBranch();
    $staff = driverOpsStaff();
    $driver = driverOpsDriver();
    $service = app(ConversationService::class);

    $conversation = $service->openDriverOps($branch, $driver, $staff);

    $this->actingAs($staff, 'branch')
        ->post(route('branch.driver-chats.messages.store', [$branch, $conversation]), [
            'body' => 'Please collect stock',
        ])
        ->assertCreated()
        ->assertJsonPath('message.sender.role_label', fn ($label) => is_string($label) && $label !== '');
});

test('customer cannot access a driver ops conversation', function () {
    $branch = driverOpsBranch();
    $staff = driverOpsStaff();
    $driver = driverOpsDriver();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $service = app(ConversationService::class);

    $conversation = $service->openDriverOps($branch, $driver, $staff);

    $this->actingAs($customer, 'web')
        ->get(route('branch.driver-chats.show', [$branch, $conversation]))
        ->assertRedirect();
});
