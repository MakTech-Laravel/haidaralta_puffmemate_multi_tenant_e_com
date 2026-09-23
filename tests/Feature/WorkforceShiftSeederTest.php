<?php

use App\Enums\ShiftAudience;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WorkforceShiftSeeder;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('the workforce seeder writes the sydney day-level roster without global periods', function () {
    $this->seed(WorkforceShiftSeeder::class);

    expect(Schema::hasTable('shift_roster_periods'))->toBeFalse()
        ->and(ShiftTemplate::query()->platformOwned()->pluck('name')->all())
        ->toContain('Network support', 'Evening ops')
        ->not->toContain('Overnight ops');

    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $daniel = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $priya = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sam = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();

    expect(ShiftTemplate::query()->where('branch_id', $sydney->id)->pluck('name')->all())
        ->toContain('Floor open', 'Late trade', 'Dispatch')
        ->and(ShiftTemplate::query()->where('name', 'Dispatch')->where('branch_id', $sydney->id)->firstOrFail()->audience)
        ->toBe(ShiftAudience::Driver)
        ->and(ShiftTemplate::query()->where('name', 'Floor open')->where('branch_id', $sydney->id)->firstOrFail()->audience)
        ->toBe(ShiftAudience::Staff)
        ->and(ShiftTemplate::query()->platformOwned()->where('name', 'Network support')->firstOrFail()->audience)
        ->toBe(ShiftAudience::Staff)
        ->and(ShiftTemplate::query()->platformOwned()->where('name', 'Evening ops')->firstOrFail()->audience)
        ->toBe(ShiftAudience::All)
        ->and(UserShiftAssignment::query()->where('user_id', $priya->id)->exists())->toBeTrue()
        ->and(UserShiftDay::query()
            ->where('user_id', $daniel->id)
            ->whereDate('duty_on', '2026-09-05')
            ->where('kind', ShiftDayKind::WeeklyOff)
            ->exists())->toBeTrue()
        ->and(UserShiftDay::query()
            ->where('user_id', $daniel->id)
            ->whereDate('duty_on', '2026-09-10')
            ->where('kind', ShiftDayKind::Leave)
            ->exists())->toBeTrue()
        ->and(UserShiftDay::query()
            ->where('user_id', $sam->id)
            ->whereDate('duty_on', '2026-09-10')
            ->onDuty()
            ->count())->toBe(2);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/shifts/index')
            ->where('workspace', 'branch')
            ->where('selectedBranchId', null)
            ->where('selectedBranch', null)
            ->has('templates', 0)
            ->has('onDuty', 0)
            ->missing('periods')
            ->missing('uncovered'));
});

test('the workforce seeder is safe to run twice', function () {
    $this->seed(WorkforceShiftSeeder::class);
    $count = UserShiftAssignment::query()->count();
    $this->seed(WorkforceShiftSeeder::class);

    expect(ShiftTemplate::query()->where('name', 'Network support')->where('owner_type', ShiftOwnerType::Platform)->count())->toBe(1)
        ->and(UserShiftAssignment::query()->count())->toBe($count);
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
