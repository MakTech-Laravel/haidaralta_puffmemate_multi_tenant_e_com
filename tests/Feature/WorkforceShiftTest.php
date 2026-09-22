<?php

use App\Enums\DriverAvailabilityStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftAudience;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use App\Models\UserShiftHistory;
use App\Models\UserTransferHistory;
use App\Support\DateTimeFormat;
use App\Support\DriverAvailability;
use App\Support\DutyWindow;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config(['drivers.single_active_branch' => true]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform assigns a shift and expands working days', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'name' => 'Morning',
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => [1, 2, 3, 4, 5, 7],
            'reason' => 'Opening coverage.',
        ])
        ->assertRedirect();

    $assignment = UserShiftAssignment::query()->where('user_id', $driver->id)->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment?->status)->toBe(ShiftAssignmentStatus::Scheduled)
        ->and($assignment?->days()->count())->toBe(15)
        ->and(UserShiftDay::query()
            ->where('user_shift_assignment_id', $assignment?->id)
            ->whereDate('duty_on', '2026-09-05')
            ->where('kind', ShiftDayKind::WeeklyOff)
            ->whereNull('shift_template_id')
            ->exists())->toBeTrue()
        ->and(UserShiftDay::query()
            ->where('user_shift_assignment_id', $assignment?->id)
            ->whereDate('duty_on', '2026-09-05')
            ->onDuty()
            ->exists())->toBeFalse()
        ->and(UserShiftHistory::query()->where('user_id', $driver->id)->count())->toBe(1)
        ->and(UserTransferHistory::query()->where('user_id', $driver->id)->exists())->toBeFalse();
});

test('leave one day removes that person from duty so another person can cover the same window', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $cover = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->forBranch($sydney)->create([
        'name' => 'Dispatch',
        'start_time' => '07:00',
        'end_time' => '15:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_ALL,
            'days' => [
                [
                    'date' => '2026-09-10',
                    'kind' => ShiftDayKind::Leave->value,
                ],
            ],
        ])
        ->assertRedirect();

    $assignment = UserShiftAssignment::query()->where('user_id', $driver->id)->firstOrFail();

    expect(UserShiftDay::query()
        ->where('user_shift_assignment_id', $assignment->id)
        ->whereDate('duty_on', '2026-09-10')
        ->where('kind', ShiftDayKind::Leave)
        ->whereNull('shift_template_id')
        ->exists())->toBeTrue();

    $this->travelTo('2026-09-10 10:00:00');

    expect(DriverAvailability::effective($driver->fresh())->value)->toBe('offline');

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $cover->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertRedirect();

    expect(UserShiftAssignment::query()
        ->where('shift_template_id', $template->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->count())->toBe(2)
        ->and(UserShiftDay::query()
            ->where('user_id', $cover->id)
            ->whereDate('duty_on', '2026-09-10')
            ->onDuty()
            ->exists())->toBeTrue();
});

test('same person can hold two non-overlapping windows on one day', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $late = ShiftTemplate::factory()->forBranch($sydney)->create([
        'name' => 'Late trade',
        'start_time' => '16:00',
        'end_time' => '23:00',
    ]);
    $dispatch = ShiftTemplate::factory()->forBranch($sydney)->create([
        'name' => 'Dispatch',
        'start_time' => '07:00',
        'end_time' => '15:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $staff->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $late->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'days_of_week' => [1, 2, 3, 4, 5, 6],
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $staff->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $dispatch->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertRedirect();

    expect(UserShiftAssignment::query()
        ->where('user_id', $staff->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->count())->toBe(2)
        ->and(UserShiftDay::query()
            ->where('user_id', $staff->id)
            ->whereDate('duty_on', '2026-09-10')
            ->onDuty()
            ->count())->toBe(2);
});

test('overlapping times for the same person are rejected', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $morning = ShiftTemplate::factory()->create([
        'start_time' => '07:00',
        'end_time' => '15:00',
    ]);
    $overlap = ShiftTemplate::factory()->create([
        'start_time' => '10:00',
        'end_time' => '18:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $morning->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.shift-assignments.create'))
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $overlap->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertRedirect(route('platform.shift-assignments.create'));

    expect(UserShiftAssignment::query()
        ->where('user_id', $driver->id)
        ->where('status', ShiftAssignmentStatus::Scheduled)
        ->count())->toBe(1);
});

test('assignment dates are not constrained by a roster period', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'start_date' => '2026-08-20',
            'end_date' => '2026-11-02',
            'days_of_week' => DriverRoster::DAYS_WEEKDAYS,
        ])
        ->assertRedirect();

    $assignment = UserShiftAssignment::query()->where('user_id', $driver->id)->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment?->start_date->toDateString())->toBe('2026-08-20')
        ->and($assignment?->end_date->toDateString())->toBe('2026-11-02');
});

test('a duty period cannot be longer than 366 days', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.shift-assignments.create'))
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-03',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertRedirect(route('platform.shift-assignments.create'))
        ->assertSessionHasErrors('end_date');
});

test('period routes are gone', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get('/platform/shifts/periods/create')
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->get('/platform/branch-shifts/periods/create')
        ->assertNotFound();

    $this->actingAs($staff, 'branch')
        ->get("/branch/{$sydney->slug}/shifts/periods/create")
        ->assertNotFound();
});

test('same-day duty window covers 10:00 and not 02:00', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $template->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);

    $driver->driverProfile?->update([
        'availability_status' => DriverAvailabilityStatus::Offline,
    ]);

    $this->travelTo(Carbon::parse('2026-09-16 10:00:00', DateTimeFormat::displayTimezone()));
    expect(DriverAvailability::effective($driver->fresh())->value)->toBe('available')
        ->and(DutyWindow::coversTime('10:00', '08:00', '17:00'))->toBeTrue()
        ->and(DutyWindow::coversTime('02:00', '08:00', '17:00'))->toBeFalse()
        ->and(DutyWindow::coversTime('02:00', '22:00', '06:00'))->toBeFalse();

    $this->travelTo(Carbon::parse('2026-09-16 02:00:00', DateTimeFormat::displayTimezone()));
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
    $assignment = UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $morning->id,
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

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
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

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $admin->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
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
        'audience' => ShiftAudience::All->value,
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

test('creating a shift stores who it is for', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-templates.store'), [
            'name' => 'Floor desk',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'owner_type' => ShiftOwnerType::Platform->value,
            'audience' => ShiftAudience::Staff->value,
        ])
        ->assertRedirect(route('platform.shift-templates.index'));

    $this->assertDatabaseHas('shift_templates', [
        'name' => 'Floor desk',
        'audience' => ShiftAudience::Staff->value,
    ]);
});

test('an invalid shift audience is rejected', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.shift-templates.create'))
        ->post(route('platform.shift-templates.store'), [
            'name' => 'Van run',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'owner_type' => ShiftOwnerType::Platform->value,
            'audience' => 'customers',
        ])
        ->assertRedirect(route('platform.shift-templates.create'))
        ->assertSessionHasErrors('audience');
});

test('updating a shift can change who it is for', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $template = ShiftTemplate::factory()->create([
        'name' => 'Dispatch desk',
        'start_time' => '07:00',
        'end_time' => '15:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.shift-templates.update', $template), [
            'name' => 'Dispatch desk',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'audience' => ShiftAudience::Driver->value,
        ])
        ->assertRedirect(route('platform.shift-templates.index'));

    $this->assertDatabaseHas('shift_templates', [
        'id' => $template->id,
        'audience' => ShiftAudience::Driver->value,
    ]);
});

test('the shifts board payload includes who each shift is for', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    ShiftTemplate::factory()->forStaff()->create(['name' => 'Floor desk']);
    ShiftTemplate::factory()->forDrivers()->create(['name' => 'Van run']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->has('templates', 2)
            ->where('templates.0.name', 'Floor desk')
            ->where('templates.0.audience', ShiftAudience::Staff->value)
            ->where('templates.0.audience_label', 'Staff only')
            ->where('templates.1.name', 'Van run')
            ->where('templates.1.audience', ShiftAudience::Driver->value)
            ->where('templates.1.audience_label', 'Drivers only')
            ->missing('uncovered'));
});

test('administrator shifts index lists only platform-owned templates', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    ShiftTemplate::factory()->create(['name' => 'Platform Morning']);
    ShiftTemplate::factory()->forBranch($sydney)->create(['name' => 'Sydney Counter']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->where('workspace', 'platform')
            ->has('templates', 1)
            ->where('templates.0.name', 'Platform Morning')
            ->missing('periods')
            ->missing('scheduled')
            ->missing('uncovered')
            ->where('templates.0.audience', ShiftAudience::All->value));

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
            ->missing('periods'));
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
            ->where('selectedBranchId', null)
            ->where('selectedBranch', null)
            ->has('templates', 0)
            ->missing('scheduled')
            ->has('onDuty', 0));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index', [
            'branch_id' => 999_999,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selectedBranchId', null)
            ->has('templates', 0));

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

test('on duty now uses the application display timezone', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->forBranch($sydney)->create([
        'name' => 'Floor open',
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $staff->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
            'start_date' => '2026-09-22',
            'end_date' => '2026-09-22',
            'days_of_week' => DriverRoster::DAYS_ALL,
        ])
        ->assertRedirect();

    $this->travelTo(Carbon::parse('2026-09-22 09:42:00', DateTimeFormat::displayTimezone()));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index', [
            'branch_id' => $sydney->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('onDuty', 1)
            ->where('onDuty.0.shift_template_name', 'Floor open')
            ->where('onDuty.0.user_id', $staff->id));
});

test('branch shift create without a store redirects to the empty board', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.create'))
        ->assertRedirect(route('platform.branch-shift-templates.index'));
});

test('branch assign without a store shows the choose-a-branch page', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/assign')
            ->where('selectedBranchId', null)
            ->where('selectedBranch', null)
            ->has('people', 0)
            ->has('templates', 0)
            ->missing('periods'));
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
            ->where('templates.0.name', 'Sydney Floor')
            ->missing('periods'));
});

test('assignment edit opens the personal calendar', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->create();
    $assignment = UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $template->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-10',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-assignments.edit', $assignment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/assign')
            ->where('assignment.id', $assignment->id)
            ->has('days'));
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

    $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
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

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-shift-assignments.store'), [
            'user_id' => $driver->id,
            'branch_id' => $sydney->id,
            'shift_template_id' => $template->id,
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
        ->and(route('platform.shift-assignments.index', absolute: false))->toBe('/platform/shifts/assigned-people')
        ->and(route('platform.branch-shift-assignments.create', absolute: false))->toBe('/platform/branch-shift-assignments')
        ->and(route('platform.branch-shift-assignments.index', absolute: false))->toBe('/platform/branch-shifts/assigned-people')
        ->and(route('branch.shift-assignments.create', $sydney, absolute: false))->toBe("/branch/{$sydney->slug}/shift-assignments")
        ->and(route('branch.shift-assignments.index', $sydney, absolute: false))->toBe("/branch/{$sydney->slug}/shifts/assigned-people");
});

test('assigned people lists name dates and shift without staff or driver labels', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $floor = ShiftTemplate::factory()->forBranch($sydney)->create(['name' => 'Floor']);
    $dispatch = ShiftTemplate::factory()->forBranch($sydney)->create(['name' => 'Dispatch']);
    $platformMorning = ShiftTemplate::factory()->create(['name' => 'Network support']);
    $melbourneFloor = ShiftTemplate::factory()->forBranch($melbourne)->create(['name' => 'Collins floor']);

    $staffAssignment = UserShiftAssignment::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $floor->id,
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-10',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $dispatch->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-15',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);
    UserShiftAssignment::factory()->cancelled()->create([
        'user_id' => $staff->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $floor->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $platformMorning->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => User::query()->where('email', UserSeeder::BRANCH_2_STAFF)->firstOrFail()->id,
        'branch_id' => $melbourne->id,
        'shift_template_id' => $melbourneFloor->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index', [
            'branch_id' => $sydney->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->missing('scheduled'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/assigned-people')
            ->where('selectedBranchId', null)
            ->has('table.data', 0));

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.index', [
            'branch_id' => $sydney->id,
        ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/assigned-people')
            ->where('workspace', 'branch')
            ->where('selectedBranchId', $sydney->id)
            ->has('table.data', 2)
            ->missing('table.data.0.role_label')
            ->missing('table.data.0.user_type')
            ->missing('table.data.0.user_type_label'));

    $rows = collect($response->inertiaProps('table.data'));

    expect($rows->pluck('user_id')->all())
        ->toContain($staff->id, $driver->id)
        ->and($rows->every(fn (array $row): bool => $row['shift_template_id'] !== $platformMorning->id))->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.index', [
            'branch_id' => $sydney->id,
            'filter' => ['search' => 'Priya'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $staffAssignment->id)
            ->where('table.data.0.user_name', 'Priya Nair')
            ->where('table.data.0.start_date', '2026-09-10')
            ->where('table.data.0.end_date', '2026-09-10'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.index', [
            'branch_id' => $sydney->id,
            'filter' => ['shift_template_id' => $dispatch->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.shift_template_name', 'Dispatch')
            ->where('table.data.0.user_id', $driver->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.index', [
            'branch_id' => $sydney->id,
            'filter' => ['people_kind' => 'driver'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.user_id', $driver->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-assignments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/assigned-people')
            ->where('workspace', 'platform')
            ->has('table.data', 1)
            ->where('table.data.0.shift_template_name', 'Network support')
            ->missing('table.data.0.role_label'));

    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($branchAdmin, 'branch')
        ->get(route('branch.shift-assignments.index', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/shifts/assigned-people')
            ->has('table.data', 3)
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['shift_template_name'] === 'Network support',
            ))
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn ($row) => $row['branch_id'] === $sydney->id && ! array_key_exists('role_label', $row),
            )));
});

test('guests cannot open assigned people', function () {
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->get(route('platform.shift-assignments.index'))
        ->assertRedirect(route('login'));

    $this->get(route('platform.branch-shift-assignments.index'))
        ->assertRedirect(route('login'));

    $this->get(route('branch.shift-assignments.index', $sydney))
        ->assertRedirect(route('login'));
});
