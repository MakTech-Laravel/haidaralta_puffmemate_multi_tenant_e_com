<?php

use App\Enums\ShiftOwnerType;
use App\Models\Branch;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WorkforceShiftSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('the workforce seeder writes named retail duty windows without faker copy', function () {
    $this->seed(WorkforceShiftSeeder::class);

    expect(ShiftTemplate::query()->platformOwned()->pluck('name')->all())
        ->toContain('Network support', 'Evening ops')
        ->not->toContain('Overnight ops')
        ->and(ShiftRosterPeriod::query()->platformOwned()->where('name', 'Spring 2026')->exists())->toBeTrue();

    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    expect(ShiftTemplate::query()->where('branch_id', $sydney->id)->pluck('name')->all())
        ->toContain('Floor open', 'Late trade', 'Dispatch')
        ->and(UserShiftAssignment::query()->count())->toBeGreaterThan(0);

    $priya = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();

    expect(UserShiftAssignment::query()->where('user_id', $priya->id)->exists())->toBeTrue();

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->where('workspace', 'branch')
            ->has('templates')
            ->has('onDuty')
            ->has('stats'));
});

test('the workforce seeder is safe to run twice', function () {
    $this->seed(WorkforceShiftSeeder::class);
    $this->seed(WorkforceShiftSeeder::class);

    expect(ShiftTemplate::query()->where('name', 'Network support')->where('owner_type', ShiftOwnerType::Platform)->count())->toBe(1)
        ->and(ShiftRosterPeriod::query()->where('name', 'September 2026')->count())->toBe(3);
});

test('the workforce seeder replaces overnight ops with evening ops', function () {
    ShiftTemplate::factory()->create([
        'name' => 'Overnight ops',
        'start_time' => '20:00',
        'end_time' => '08:00',
    ]);

    $this->seed(WorkforceShiftSeeder::class);

    expect(ShiftTemplate::query()->where('name', 'Overnight ops')->exists())->toBeFalse()
        ->and(ShiftTemplate::query()->platformOwned()->where('name', 'Evening ops')->count())->toBe(1);
});
