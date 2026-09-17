<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\DriverRoster;
use App\Models\User;
use App\Support\DriverAvailability;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can list and create drivers with branch assignments', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/index')
            ->has('table.data'));

    $seededDriver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/create')
            ->has('branches')
            ->has('vehicleTypes')
            ->has('availabilityStatuses')
            ->has('rosterStatuses'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.edit', $seededDriver))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/edit')
            ->has('driver')
            ->has('rosterStatuses'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.store'), [
            'name' => 'Taylor Driver',
            'phone' => '+61420000001',
            'email' => 'taylor.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Car->value,
            'availability_status' => DriverAvailabilityStatus::Available->value,
            'branch_ids' => [$sydney->id, $melbourne->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61420000001')->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and($driver->branch_id)->toBeNull()
        ->and($driver->driverProfile)->not->toBeNull()
        ->and(DriverBranchAssignment::query()->where('driver_id', $driver->id)->where('status', DriverAssignmentStatus::Active)->count())->toBe(2);
});

test('seeded driver has profile and multi branch assignments keyed by user id', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and(DriverProfile::query()->where('user_id', $driver->id)->exists())->toBeTrue()
        ->and(DriverBranchAssignment::query()->where('driver_id', $driver->id)->count())->toBe(2);
});

test('platform can create a driver with a nested duty period', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.store'), [
            'name' => 'Period Driver',
            'phone' => '+61420000011',
            'email' => 'period.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Car->value,
            'availability_status' => DriverAvailabilityStatus::Offline->value,
            'branch_ids' => [$sydney->id],
            'is_active' => true,
            'rosters' => [[
                'branch_id' => $sydney->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-10-15',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'days_of_week' => DriverRoster::DAYS_ALL,
                'status' => 'scheduled',
            ]],
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61420000011')->firstOrFail();
    $roster = DriverRoster::query()->where('driver_id', $driver->id)->firstOrFail();

    expect($roster->start_date?->toDateString())->toBe('2026-09-01')
        ->and($roster->end_date?->toDateString())->toBe('2026-10-15')
        ->and($roster->start_time?->format('H:i'))->toBe('08:00')
        ->and($roster->end_time?->format('H:i'))->toBe('17:00')
        ->and($roster->normalizedDaysOfWeek())->toBe(DriverRoster::DAYS_ALL);
});

test('platform can add roster for actively assigned branch and rejects overlap', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.rosters.store', $driver), [
            'branch_id' => $sydney->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-15',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => 'scheduled',
        ])
        ->assertRedirect();

    expect(DriverRoster::query()->where('driver_id', $driver->id)->count())->toBe(1);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.drivers.show', $driver))
        ->post(route('platform.drivers.rosters.store', $driver), [
            'branch_id' => $sydney->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => 'scheduled',
        ])
        ->assertRedirect();

    expect(DriverRoster::query()->where('driver_id', $driver->id)->count())->toBe(1);
});

test('platform allows weekday and weekend periods that share dates', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.rosters.store', $driver), [
            'branch_id' => $sydney->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-15',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'days_of_week' => DriverRoster::DAYS_WEEKDAYS,
            'status' => 'scheduled',
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.rosters.store', $driver), [
            'branch_id' => $sydney->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-15',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'days_of_week' => [6, 7],
            'status' => 'scheduled',
        ])
        ->assertRedirect();

    expect(DriverRoster::query()->where('driver_id', $driver->id)->count())->toBe(2);
});

test('branch can create driver auto assigned to current branch', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.drivers.index', $sydney))
        ->assertOk();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.drivers.create', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/drivers/create')
            ->has('rosterStatuses'));

    $this->actingAs($staff, 'branch')
        ->post(route('branch.drivers.store', ['branch' => $sydney->slug]), [
            'name' => 'Branch Driver',
            'phone' => '0412000099',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Bicycle->value,
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61412000099')->first()
        ?? User::query()->where('phone', '0412000099')->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and(DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('branch_id', $sydney->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->exists())->toBeTrue();
});

test('platform nested branch drivers index lists assigned drivers', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.drivers.index', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/drivers/index')
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['phone'] === UserSeeder::DRIVER_PHONE,
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.drivers.create', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/drivers/create')
            ->has('rosterStatuses'));
});

test('platform can store driver with address and avatar and sync branches', function () {
    Storage::fake('public');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.store'), [
            'name' => 'Ava Driver',
            'phone' => '+61420000077',
            'email' => 'ava.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Motorcycle->value,
            'availability_status' => DriverAvailabilityStatus::Offline->value,
            'address' => '12 Delivery Lane, Sydney',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'branch_ids' => [$sydney->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61420000077')->firstOrFail();
    $profile = $driver->driverProfile;

    expect($profile?->address)->toBe('12 Delivery Lane, Sydney')
        ->and($driver->avatar)->not->toBeNull();

    Storage::disk('public')->assertExists($driver->avatar);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.drivers.branches.sync', $driver), [
            'branch_ids' => [$sydney->id, $melbourne->id],
        ])
        ->assertRedirect();

    expect(DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('status', DriverAssignmentStatus::Active)
        ->count())->toBe(2);
});

test('platform can update a driver avatar onto users.avatar', function () {
    Storage::fake('public');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $profile = $driver->driverProfile;

    $this->actingAs($admin, 'platform')
        ->put(route('platform.drivers.update', $driver), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'email' => $driver->email,
            'vehicle_type' => $profile?->vehicle_type?->value ?? DriverVehicleType::Car->value,
            'availability_status' => $profile?->availability_status?->value ?? DriverAvailabilityStatus::Offline->value,
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('driver-avatar.jpg'),
        ])
        ->assertRedirect();

    $driver->refresh();

    expect($driver->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($driver->avatar);
});

test('driver list payload includes branch slug and effective availability from roster', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->travelTo('2026-09-16 10:00:00');

    $driver->driverProfile?->update([
        'availability_status' => DriverAvailabilityStatus::Offline,
    ]);

    DriverRoster::query()->create([
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-15',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'days_of_week' => DriverRoster::DAYS_WEEKDAYS,
        'status' => 'scheduled',
    ]);

    expect(DriverAvailability::effective($driver->fresh())->value)
        ->toBe(DriverAvailabilityStatus::Available->value);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/index')
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['id'] === $driver->id
                    && $row['effective_availability'] === 'available'
                    && collect($row['branches'])->contains(
                        fn ($branch) => ($branch['slug'] ?? null) === BranchSeeder::SYDNEY_CBD,
                    ),
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.show', $driver))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/show')
            ->where('driver.effective_availability', 'available')
            ->where('driver.rosters.0.period_label', '1 Sep 2026 – 15 Oct 2026')
            ->where('driver.rosters.0.days_label', 'Mon–Fri'));
});

test('platform branch options endpoint supports search pagination', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.drivers.branch-options', [
            'filter' => ['search' => 'Sydney', 'status' => '1'],
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonFragment(['slug' => BranchSeeder::SYDNEY_CBD]);
});

test('busy override wins over scheduled roster for effective availability', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $driver->driverProfile?->update([
        'availability_status' => DriverAvailabilityStatus::Busy,
    ]);

    DriverRoster::query()->create([
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'start_time' => now()->copy()->subHour()->format('H:i'),
        'end_time' => now()->copy()->addHour()->format('H:i'),
        'days_of_week' => DriverRoster::DAYS_ALL,
        'status' => 'scheduled',
    ]);

    expect(DriverAvailability::effective($driver)->value)
        ->toBe(DriverAvailabilityStatus::Busy->value);
});
