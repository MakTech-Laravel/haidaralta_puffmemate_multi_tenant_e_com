<?php

use App\Enums\DriverRosterStatus;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserTransferHistory;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config(['drivers.single_active_branch' => true]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('backfill command is idempotent for assignment dates rosters and inferred transfers', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $active = DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('branch_id', $sydney->id)
        ->firstOrFail();
    $active->forceFill(['started_at' => null, 'ended_at' => null])->save();

    DriverBranchAssignment::factory()->inactive()->create([
        'driver_id' => $driver->id,
        'branch_id' => $melbourne->id,
        'started_at' => null,
        'ended_at' => null,
    ]);

    DriverRoster::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-15',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'days_of_week' => DriverRoster::DAYS_ALL,
        'status' => DriverRosterStatus::Scheduled,
    ]);

    Artisan::call('workforce:backfill-transfers-and-shifts');
    Artisan::call('workforce:backfill-transfers-and-shifts');

    expect($active->fresh()->started_at)->not->toBeNull()
        ->and(UserTransferHistory::query()->where('user_id', $driver->id)->count())->toBe(1)
        ->and(UserShiftAssignment::query()->where('user_id', $driver->id)->count())->toBe(1)
        ->and(ShiftTemplate::query()->where('name', 'Migrated 08:00–17:00')->count())->toBe(1)
        ->and($assignment = UserShiftAssignment::query()->where('user_id', $driver->id)->first())->not->toBeNull()
        ->and($assignment?->days()->count())->toBe(15);
});
