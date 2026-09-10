<?php

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can view activity log index and show', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::factory()->create(['name' => 'Logged Branch']);

    activity()
        ->causedBy($admin)
        ->performedOn($branch)
        ->event('updated')
        ->withProperty('note', 'test')
        ->tap(function (Activity $activity): void {
            $activity->attribute_changes = [
                'attributes' => ['name' => 'Logged Branch'],
                'old' => ['name' => 'Old Name'],
            ];
        })
        ->log('branch-audit-unique-marker');

    $activity = Activity::query()
        ->where('description', 'branch-audit-unique-marker')
        ->latest('id')
        ->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.activity-logs.index', [
            'search' => 'branch-audit-unique-marker',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/activity-logs/index')
            ->has('activities.data', 1)
            ->where('activities.data.0.id', $activity->id)
            ->where('activities.data.0.event', 'updated')
            ->where('activities.data.0.causer.name', $admin->name)
            ->where('activities.data.0.subject.label', 'Logged Branch')
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.activity-logs.show', $activity))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/activity-logs/show')
            ->where('activity.id', $activity->id)
            ->where('activity.attribute_changes.old.name', 'Old Name')
            ->where('activity.attribute_changes.attributes.name', 'Logged Branch')
            ->where('activity.properties.note', 'test')
        );
});

test('branch create is written to activity log without hidden fields on user', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform');

    $branch = Branch::factory()->create(['name' => 'Audited Branch Create']);

    $activity = Activity::query()
        ->where('subject_type', Branch::class)
        ->where('subject_id', $branch->id)
        ->where('event', 'created')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('portal'))->toBe('platform');

    $user = User::factory()->create([
        'password' => 'SecretPass123!',
        'user_type' => UserType::PLATFORM,
    ]);

    $userActivity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'created')
        ->latest('id')
        ->first();

    expect($userActivity)->not->toBeNull();

    $changes = $userActivity->attribute_changes?->toArray() ?? [];
    $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];

    expect($attributes)->not->toHaveKey('password')
        ->and($attributes)->not->toHaveKey('two_factor_secret');
});

test('platform staff without view activity logs cannot view activity logs', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    $activity = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'secret',
        'event' => 'updated',
    ]);

    $this->actingAs($user, 'platform')
        ->get(route('platform.activity-logs.index'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route('platform.activity-logs.show', $activity))
        ->assertForbidden();
});

test('activity log index filters by event', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    Activity::query()->delete();

    $branch = Branch::factory()->create();

    activity()->causedBy($admin)->performedOn($branch)->event('created')->log('created');
    activity()->causedBy($admin)->performedOn($branch)->event('updated')->log('updated');

    $this->actingAs($admin, 'platform')
        ->get(route('platform.activity-logs.index', ['event' => 'updated']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/activity-logs/index')
            ->has('activities.data', 1)
            ->where('activities.data.0.event', 'updated')
            ->where('filters.event', 'updated')
        );
});

test('branch portal sees only its branch activities', function () {
    $branchA = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $branchB = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $staffA = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    Activity::query()->delete();

    activity()
        ->causedBy($staffA)
        ->performedOn($branchA)
        ->withProperty('branch_id', $branchA->id)
        ->event('updated')
        ->log('branch-a-marker');

    activity()
        ->performedOn($branchB)
        ->withProperty('branch_id', $branchB->id)
        ->event('updated')
        ->log('branch-b-marker');

    $this->actingAs($staffA, 'branch')
        ->get(route('branch.activity-logs.index', $branchA))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/activity-logs/index')
            ->has('activities.data', 1)
            ->where('activities.data.0.description', 'branch-a-marker')
        );

    $other = Activity::query()->where('description', 'branch-b-marker')->firstOrFail();

    $this->actingAs($staffA, 'branch')
        ->get(route('branch.activity-logs.show', [$branchA, $other->id]))
        ->assertNotFound();
});

test('driver only sees own causer activities and guests are redirected', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $other = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    Activity::query()->delete();

    activity()->causedBy($driver)->event('login')->log('driver-own-login');
    activity()->causedBy($other)->event('login')->log('driver-other-login');

    $this->actingAs($driver, 'driver')
        ->get(route('driver.activity.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('driver/activity/index')
            ->has('activities.data', 1)
            ->where('activities.data.0.description', 'driver-own-login')
        );

    auth()->guard('driver')->logout();

    $this->get(route('driver.activity.index'))->assertRedirect();
});

test('customer can open my activity', function () {
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    Activity::query()->delete();

    activity()->causedBy($customer)->event('login')->log('customer-login');

    $this->actingAs($customer, 'customer')
        ->get(route('customer.activity.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/activity/index')
            ->has('activities.data', 1)
        );
});
