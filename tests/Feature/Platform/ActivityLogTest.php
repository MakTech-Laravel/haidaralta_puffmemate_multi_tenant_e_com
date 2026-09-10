<?php

use App\Enums\RoleEnum;
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

test('platform staff without manage reports cannot view activity logs', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

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
