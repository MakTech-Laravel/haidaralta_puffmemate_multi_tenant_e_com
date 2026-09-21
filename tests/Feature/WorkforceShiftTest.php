<?php

use App\Enums\DriverAvailabilityStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftHistory;
use App\Models\UserTransferHistory;
use App\Support\DriverAvailability;
use App\Support\DutyWindow;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    config(['drivers.single_active_branch' => true]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform assigns a shift with required branch and roster period', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'name' => 'Morning',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);
    $period = ShiftRosterPeriod::factory()->create([
        'name' => 'September',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'shift_roster_period_id' => $period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_ALL,
            'reason' => 'Opening coverage.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('user_shift_assignments', [
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $template->id,
        'shift_roster_period_id' => $period->id,
        'status' => ShiftAssignmentStatus::Scheduled->value,
    ]);

    expect(UserShiftHistory::query()->where('user_id', $driver->id)->count())->toBe(1)
        ->and(UserTransferHistory::query()->where('user_id', $driver->id)->exists())->toBeFalse();
});

test('same-day duty window covers 10:00 and not 02:00', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);
    $period = ShiftRosterPeriod::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $template->id,
        'shift_roster_period_id' => $period->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);

    $driver->driverProfile?->update([
        'availability_status' => DriverAvailabilityStatus::Offline,
    ]);

    $this->travelTo('2026-09-16 10:00:00');
    expect(DriverAvailability::effective($driver->fresh())->value)->toBe('available')
        ->and(DutyWindow::coversTime('10:00', '08:00', '17:00'))->toBeTrue()
        ->and(DutyWindow::coversTime('02:00', '08:00', '17:00'))->toBeFalse()
        ->and(DutyWindow::coversTime('02:00', '22:00', '06:00'))->toBeFalse();

    $this->travelTo('2026-09-16 02:00:00');
    expect(DriverAvailability::effective($driver->fresh())->value)->toBe('offline');
});

test('platform rejects a shift that wraps past midnight', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.shift-templates.create'))
        ->post(route('platform.shift-templates.store'), [
            'name' => 'Night wrap',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'owner_type' => ShiftOwnerType::Platform->value,
        ])
        ->assertRedirect(route('platform.shift-templates.create'))
        ->assertSessionHasErrors('end_time');

    expect(ShiftTemplate::query()->where('name', 'Night wrap')->exists())->toBeFalse();
});

test('changing a shift writes shift history only', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $morning = ShiftTemplate::factory()->create([
        'name' => 'Morning',
        'start_time' => '08:00',
        'end_time' => '16:00',
    ]);
    $evening = ShiftTemplate::factory()->create([
        'name' => 'Evening',
        'start_time' => '16:00',
        'end_time' => '22:00',
    ]);
    $period = ShiftRosterPeriod::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);
    $assignment = UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $morning->id,
        'shift_roster_period_id' => $period->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-15',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.shift-assignments.update', $assignment), [
            'shift_template_id' => $evening->id,
            'reason' => 'Moved to evening.',
        ])
        ->assertRedirect();

    expect(UserShiftHistory::query()->where('user_id', $driver->id)->count())->toBe(1)
        ->and(UserTransferHistory::query()->where('user_id', $driver->id)->exists())->toBeFalse();

    $this->assertDatabaseHas('user_shift_histories', [
        'user_id' => $driver->id,
        'previous_shift_template_id' => $morning->id,
        'new_shift_template_id' => $evening->id,
        'reason' => 'Moved to evening.',
    ]);
});

test('weekday-only and weekend-only assignments that share dates are allowed', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);
    $period = ShiftRosterPeriod::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'shift_roster_period_id' => $period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_WEEKDAYS,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'shift_roster_period_id' => $period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => [6, 7],
        ])
        ->assertRedirect();

    expect(UserShiftAssignment::query()
        ->where('user_id', $driver->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->count())->toBe(2);
});

test('platform users cannot be assigned a shift', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create();
    $period = ShiftRosterPeriod::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $admin->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'shift_roster_period_id' => $period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertNotFound();

    expect(UserShiftAssignment::query()->where('user_id', $admin->id)->exists())->toBeFalse()
        ->and($admin->fresh()->user_type)->toBe(UserType::PLATFORM);
});

test('platform creates a unique-named shift template', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-templates.store'), [
            'name' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'owner_type' => ShiftOwnerType::Platform->value,
        ])
        ->assertRedirect(route('platform.shift-templates.index'));

    $this->assertDatabaseHas('shift_templates', [
        'name' => 'Morning',
        'owner_type' => ShiftOwnerType::Platform->value,
    ]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.shift-templates.create'))
        ->post(route('platform.shift-templates.store'), [
            'name' => 'Morning',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'owner_type' => ShiftOwnerType::Platform->value,
        ])
        ->assertRedirect(route('platform.shift-templates.create'))
        ->assertSessionHasErrors('name');
});

test('administrator shifts index lists only platform-owned templates', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    ShiftTemplate::factory()->create(['name' => 'Platform Morning']);
    ShiftTemplate::factory()->forBranch($sydney)->create(['name' => 'Sydney Counter']);
    ShiftRosterPeriod::factory()->create(['name' => 'Platform September']);
    ShiftRosterPeriod::factory()->forBranch($sydney)->create(['name' => 'Sydney September']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->where('workspace', 'platform')
            ->has('templates', 1)
            ->where('templates.0.name', 'Platform Morning')
            ->has('periods', 1)
            ->where('periods.0.name', 'Platform September'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index', [
            'branch_id' => $sydney->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->where('workspace', 'branch')
            ->where('selectedBranchId', $sydney->id)
            ->has('templates', 1)
            ->where('templates.0.name', 'Sydney Counter')
            ->has('periods', 1)
            ->where('periods.0.name', 'Sydney September'));
});

test('branch shifts board loads one store at a time', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();

    ShiftTemplate::factory()->forBranch($sydney)->create(['name' => 'Sydney Floor']);
    ShiftTemplate::factory()->forBranch($melbourne)->create(['name' => 'Melbourne Floor']);
    ShiftTemplate::factory()->forBranch($brisbane)->create(['name' => 'Brisbane Floor']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->where('selectedBranchId', $brisbane->id)
            ->where('selectedBranch.id', $brisbane->id)
            ->where('selectedBranch.name', $brisbane->name)
            ->has('templates', 1)
            ->where('templates.0.name', 'Brisbane Floor'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index', [
            'branch_id' => $sydney->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selectedBranchId', $sydney->id)
            ->has('templates', 1)
            ->where('templates.0.name', 'Sydney Floor'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index', [
            'branch_id' => $melbourne->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selectedBranchId', $melbourne->id)
            ->has('templates', 1)
            ->where('templates.0.name', 'Melbourne Floor'));
});

test('branch assign page only lists the selected store windows', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    ShiftTemplate::factory()->forBranch($sydney)->create(['name' => 'Sydney Floor']);
    ShiftTemplate::factory()->forBranch($melbourne)->create(['name' => 'Melbourne Floor']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.create', [
            'branch_id' => $sydney->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/assign')
            ->where('selectedBranchId', $sydney->id)
            ->has('templates', 1)
            ->where('templates.0.name', 'Sydney Floor'));
});

test('branch shift options page active stores twenty at a time', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    Branch::factory()->create([
        'name' => 'Closed Store',
        'status' => false,
    ]);
    Branch::factory()->count(18)->create();

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.branch-shift-templates.branch-options'))
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.total', 21)
        ->assertJsonMissing(['name' => 'Closed Store']);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.branch-shift-templates.branch-options', [
            'filter' => ['search' => 'Melbourne'],
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $melbourne->id);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.branch-shift-templates.branch-options', [
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

test('guests cannot list branch shift options', function () {
    $this->getJson(route('platform.branch-shift-templates.branch-options'))
        ->assertRedirect(route('login'));
});

test('administrator cannot open a branch-owned shift template', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $platform = ShiftTemplate::factory()->create();
    $branchOwned = ShiftTemplate::factory()->forBranch($sydney)->create();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-templates.edit', $branchOwned))
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.edit', $platform))
        ->assertNotFound();
});

test('administrator store ignores a posted branch owner', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-templates.store'), [
            'name' => 'Desk',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'owner_type' => ShiftOwnerType::Branch->value,
            'branch_id' => $sydney->id,
        ])
        ->assertRedirect(route('platform.shift-templates.index'));

    $this->assertDatabaseHas('shift_templates', [
        'name' => 'Desk',
        'owner_type' => ShiftOwnerType::Platform->value,
        'branch_id' => null,
    ]);
});

test('branch management creates a branch-owned shift template', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-templates.store'), [
            'name' => 'Counter',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'owner_type' => ShiftOwnerType::Platform->value,
            'branch_id' => $sydney->id,
        ])
        ->assertRedirect(route('platform.branch-shift-templates.index', [
            'branch_id' => $sydney->id,
        ]));

    $this->assertDatabaseHas('shift_templates', [
        'name' => 'Counter',
        'owner_type' => ShiftOwnerType::Branch->value,
        'branch_id' => $sydney->id,
    ]);
});

test('administrator cannot assign a branch-owned shift template', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->forBranch($sydney)->create();
    $period = ShiftRosterPeriod::factory()->forBranch($sydney)->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'shift_roster_period_id' => $period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertNotFound();
});

test('branch management cannot assign a platform-owned shift template', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create();
    $period = ShiftRosterPeriod::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'shift_roster_period_id' => $period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertNotFound();
});

test('old assign urls redirect to sibling paths so the shifts item is not also active', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get('/platform/shifts/assign')
        ->assertRedirect('/platform/shift-assignments');

    expect(route('platform.shift-templates.index', absolute: false))->toBe('/platform/shifts')
        ->and(route('platform.branch-shift-templates.index', absolute: false))->toBe('/platform/branch-shifts')
        ->and(route('platform.shift-assignments.create', absolute: false))->toBe('/platform/shift-assignments')
        ->and(route('platform.branch-shift-assignments.create', absolute: false))->toBe('/platform/branch-shift-assignments')
        ->and(route('branch.shift-assignments.create', $sydney, absolute: false))->toBe("/branch/{$sydney->slug}/shift-assignments");
});
