<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Driver\DriverService;
use App\Support\DriverAvailability;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['drivers.single_active_branch' => true]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can list and create drivers with branch assignments', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

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
            ->where('drivers.single_active_branch', true));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.edit', $seededDriver))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/edit')
            ->has('driver')
            ->missing('rosterStatuses'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.store'), [
            'name' => 'Taylor Driver',
            'phone' => '+61420000001',
            'email' => 'taylor.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Car->value,
            'availability_status' => DriverAvailabilityStatus::Available->value,
            'branch_ids' => [$sydney->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61420000001')->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and($driver->branch_id)->toBeNull()
        ->and($driver->driverProfile)->not->toBeNull()
        ->and(DriverBranchAssignment::query()->where('driver_id', $driver->id)->where('status', DriverAssignmentStatus::Active)->count())->toBe(1);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);
});

test('platform drivers index can search by phone substring', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('phone', '+61420101006')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.index', [
            'filter' => ['search' => '20101006'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/index')
            ->where('table.filters.search', '20101006')
            ->has('table.data', 1)
            ->where('table.data.0.id', $driver->id)
            ->where('table.data.0.phone', '+61420101006'));
});

test('platform drivers index falls back to sql when meilisearch returns no hits', function () {
    config(['scout.driver' => 'meilisearch']);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $driver = User::factory()->create([
        'name' => 'Fallback Courier',
        'phone' => '+61420999887',
        'email' => 'fallback.courier@puffmemate.dev',
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
    ]);

    DriverProfile::query()->create([
        'user_id' => $driver->id,
        'vehicle_type' => DriverVehicleType::Car->value,
        'availability_status' => DriverAvailabilityStatus::Offline->value,
    ]);

    DriverBranchAssignment::query()->create([
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.index', [
            'filter' => ['search' => '999887'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/index')
            ->where('table.filters.search', '999887')
            ->has('table.data', 1)
            ->where('table.data.0.phone', '+61420999887'));
});

test('seeded driver has profile and one active branch assignment keyed by user id', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and(DriverProfile::query()->where('user_id', $driver->id)->exists())->toBeTrue()
        ->and(DriverBranchAssignment::query()->where('driver_id', $driver->id)->count())->toBe(1);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);
});

test('platform create ignores nested duty periods and does not write driver rosters', function () {
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

    expect(DriverRoster::query()->where('driver_id', $driver->id)->exists())->toBeFalse();
});

test('driver list payload includes branch slug and effective availability from shift assignments', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->travelTo('2026-09-16 10:00:00');

    $driver->driverProfile?->update([
        'availability_status' => DriverAvailabilityStatus::Offline,
    ]);

    $template = ShiftTemplate::factory()->create([
        'start_time' => '08:00',
        'end_time' => '17:00',
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $template->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-15',
        'days_of_week' => DriverRoster::DAYS_WEEKDAYS,
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
            ->where('driver.shift_assignments.0.days_label', 'Mon–Fri'));
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
            ->missing('rosterStatuses'));

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

test('platform nested branch drivers index redirects to the drivers list filtered by branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $melbourneOnly = User::factory()->create([
        'name' => 'Melbourne Only Courier',
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
    ]);

    DriverBranchAssignment::factory()->create([
        'driver_id' => $melbourneOnly->id,
        'branch_id' => $melbourne->id,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.drivers.index', $sydney))
        ->assertRedirect(route('platform.drivers.index', [
            'filter' => ['branch_id' => $sydney->id],
        ]));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.index', [
            'filter' => ['branch_id' => $sydney->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/index')
            ->where('table.filters.branch_id', (string) $sydney->id)
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['phone'] === UserSeeder::DRIVER_PHONE,
            ))
            ->where('table.data', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => $row['id'] === $melbourneOnly->id,
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.drivers.create', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/drivers/create')
            ->missing('rosterStatuses'));
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
        ->and($driver->hasMedia('avatar'))->toBeTrue()
        ->and($driver->getFirstMedia('avatar')->mime_type)->toBe('image/webp');

    $this->actingAs($admin, 'platform')
        ->put(route('platform.drivers.branches.sync', $driver), [
            'branch_ids' => [$melbourne->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Inactive->value,
    ]);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $melbourne->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);

    expect(DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('status', DriverAssignmentStatus::Active)
        ->count())->toBe(1);
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

    expect($driver->hasMedia('avatar'))->toBeTrue()
        ->and($driver->getFirstMedia('avatar')->mime_type)->toBe('image/webp');
});

test('busy override wins over a scheduled shift assignment for effective availability', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $driver->driverProfile?->update([
        'availability_status' => DriverAvailabilityStatus::Busy,
    ]);

    $template = ShiftTemplate::factory()->create([
        'start_time' => now()->copy()->subHour()->format('H:i'),
        'end_time' => now()->copy()->addHour()->format('H:i'),
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $sydney->id,
        'shift_template_id' => $template->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'days_of_week' => DriverRoster::DAYS_ALL,
    ]);

    expect(DriverAvailability::effective($driver)->value)
        ->toBe(DriverAvailabilityStatus::Busy->value);
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

test('platform rejects assigning a driver to two branches when a single active branch is required', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.drivers.create'))
        ->post(route('platform.drivers.store'), [
            'name' => 'Two Branch Driver',
            'phone' => '+61420000021',
            'email' => 'two.branch.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Car->value,
            'availability_status' => DriverAvailabilityStatus::Offline->value,
            'branch_ids' => [$sydney->id, $melbourne->id],
            'is_active' => true,
        ])
        ->assertRedirect(route('platform.drivers.create'))
        ->assertSessionHasErrors([
            'branch_ids' => DriverService::ONE_ACTIVE_BRANCH_MESSAGE,
        ]);

    expect(User::query()->where('phone', '+61420000021')->exists())->toBeFalse();
});

test('platform rejects syncing two active branches when a single active branch is required', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.drivers.show', $driver))
        ->put(route('platform.drivers.branches.sync', $driver), [
            'branch_ids' => [$sydney->id, $melbourne->id],
        ])
        ->assertRedirect(route('platform.drivers.show', $driver))
        ->assertSessionHasErrors([
            'branch_ids' => DriverService::ONE_ACTIVE_BRANCH_MESSAGE,
        ]);

    expect(DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('status', DriverAssignmentStatus::Active)
        ->count())->toBe(1);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);
});

test('branch drivers index only lists this branch and never exposes assignable drivers', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $otherBranchDriver = User::query()->where('phone', '+61420101008')->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.drivers.index', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/drivers/index')
            ->missing('assignableDrivers')
            ->where('table.data', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => $row['id'] === $otherBranchDriver->id,
            ))
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn ($row) => array_key_exists('avatar_url', $row),
            )));

    expect(Route::has('branch.drivers.assign'))->toBeFalse();
});

test('branch drivers index supports availability assignment vehicle and status filters', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $motorcycleDriver = User::query()->where('phone', '+61488888888')->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.drivers.index', [
            'branch' => $sydney,
            'filter' => [
                'vehicle_type' => DriverVehicleType::Motorcycle->value,
                'assignment_status' => DriverAssignmentStatus::Active->value,
                'is_active' => '1',
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/drivers/index')
            ->where('table.filters.vehicle_type', DriverVehicleType::Motorcycle->value)
            ->where('table.filters.assignment_status', DriverAssignmentStatus::Active->value)
            ->where('table.filters.is_active', '1')
            ->has('table.filterOptions.vehicle_type')
            ->has('table.filterOptions.availability_status')
            ->has('table.filterOptions.assignment_status')
            ->has('table.filterOptions.is_active')
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['id'] === $motorcycleDriver->id
                    && $row['vehicle_type_label'] === 'Motorcycle',
            ))
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn ($row) => $row['vehicle_type_label'] === 'Motorcycle'
                    && $row['is_active'] === true
                    && $row['assignment_status_label'] === 'Active',
            )));
});

test('platform creates a driver with two active branches when multiple are allowed', function () {
    config(['drivers.single_active_branch' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.store'), [
            'name' => 'Two Branch Driver',
            'phone' => '+61420000022',
            'email' => 'multi.branch.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Car->value,
            'availability_status' => DriverAvailabilityStatus::Offline->value,
            'branch_ids' => [$sydney->id, $melbourne->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61420000022')->firstOrFail();

    expect(DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('status', DriverAssignmentStatus::Active)
        ->count())->toBe(2);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $melbourne->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);
});

test('platform syncs two active branches when multiple are allowed', function () {
    config(['drivers.single_active_branch' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

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

test('branch cannot assign an existing driver via the removed assign endpoint', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_2_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    expect(Route::has('branch.drivers.assign'))->toBeFalse();

    $this->actingAs($staff, 'branch')
        ->post("/branch/{$melbourne->slug}/drivers/assign", [
            'driver_id' => $driver->id,
            'status' => DriverAssignmentStatus::Active->value,
        ])
        ->assertNotFound();

    expect(DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('branch_id', $melbourne->id)
        ->where('status', DriverAssignmentStatus::Active)
        ->exists())->toBeFalse();
});

test('seeded driver has two active branches when multiple are allowed', function () {
    config(['drivers.single_active_branch' => false]);

    $this->seed(UserSeeder::class);

    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    expect(DriverBranchAssignment::query()
        ->where('driver_id', $driver->id)
        ->where('status', DriverAssignmentStatus::Active)
        ->count())->toBe(2);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);

    $this->assertDatabaseHas('driver_branch_assignments', [
        'driver_id' => $driver->id,
        'branch_id' => $melbourne->id,
        'status' => DriverAssignmentStatus::Active->value,
    ]);
});
