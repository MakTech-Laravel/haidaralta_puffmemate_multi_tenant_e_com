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

test('overnight duty window covers 02:00 and not 12:00', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->overnight()->create();
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

    $this->travelTo('2026-09-16 02:00:00');
    expect(DriverAvailability::effective($driver->fresh())->value)->toBe('available')
        ->and(DutyWindow::coversTime('02:00', '22:00', '06:00', true))->toBeTrue()
        ->and(DutyWindow::coversTime('12:00', '22:00', '06:00', true))->toBeFalse();

    $this->travelTo('2026-09-16 12:00:00');
    expect(DriverAvailability::effective($driver->fresh())->value)->toBe('offline');
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
        'is_overnight' => false,
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
