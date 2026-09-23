<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\RoleEnum;
use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\User;
use App\Models\UserTransferHistory;
use App\Services\Workforce\TransferEmployeeService;
use App\Support\SuperAdmin;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    config(['drivers.single_active_branch' => true]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform transfers a driver to another branch and writes branch_change history', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.transfer', $driver), [
            'to_branch_id' => $melbourne->id,
            'effective_date' => '2026-09-21',
            'reason' => 'Coverage in Melbourne.',
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
        ->count())->toBe(1)
        ->and($driver->fresh()->user_type)->toBe(UserType::DRIVER);

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $driver->id,
        'subject_type' => TransferSubjectType::Driver->value,
        'transfer_type' => TransferType::BranchChange->value,
        'from_branch_id' => $sydney->id,
        'to_branch_id' => $melbourne->id,
        'from_role' => TransferEmployeeService::DRIVER_ROLE,
        'to_role' => TransferEmployeeService::DRIVER_ROLE,
        'reason' => 'Coverage in Melbourne.',
    ]);
});

test('driver transfer rejects a role field and never changes user type', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.drivers.show', $driver))
        ->post(route('platform.drivers.transfer', $driver), [
            'to_branch_id' => $melbourne->id,
            'effective_date' => '2026-09-21',
            'reason' => 'Attempted role change.',
            'roles' => [RoleEnum::STAFF->value],
        ])
        ->assertRedirect(route('platform.drivers.show', $driver))
        ->assertSessionHasErrors('roles');

    expect($driver->fresh()->user_type)->toBe(UserType::DRIVER)
        ->and(UserTransferHistory::query()->where('user_id', $driver->id)->exists())->toBeFalse();
});

test('multi-active driver sync keeps two actives and writes history for the added branch', function () {
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

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $driver->id,
        'subject_type' => TransferSubjectType::Driver->value,
        'transfer_type' => TransferType::BranchChange->value,
        'to_branch_id' => $melbourne->id,
    ]);
});

test('platform transfers branch staff and remaps spatiel roles onto the destination team', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.transfer', $staff), [
            'to_branch_id' => $melbourne->id,
            'roles' => [RoleEnum::ADMIN->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Cover Melbourne floor.',
        ])
        ->assertRedirect();

    expect($staff->fresh()->branch_id)->toBe($melbourne->id)
        ->and($staff->fresh()->user_type)->toBe(UserType::BRANCH);

    app(PermissionRegistrar::class)->setPermissionsTeamId($melbourne->id);
    expect($staff->fresh()->hasRole(RoleEnum::ADMIN->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId($sydney->id);
    expect($staff->fresh()->hasRole(RoleEnum::STAFF->value))->toBeFalse();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $staff->id,
        'subject_type' => TransferSubjectType::BranchStaff->value,
        'transfer_type' => TransferType::BranchChange->value,
        'from_branch_id' => $sydney->id,
        'to_branch_id' => $melbourne->id,
        'to_role' => RoleEnum::ADMIN->value,
    ]);
});

test('staff role change on the same branch writes one role_change row', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.transfer', $staff), [
            'to_branch_id' => $sydney->id,
            'roles' => [RoleEnum::MANAGER->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Promotion.',
        ])
        ->assertRedirect();

    expect($staff->fresh()->branch_id)->toBe($sydney->id)
        ->and(UserTransferHistory::query()->where('user_id', $staff->id)->count())->toBe(1);

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $staff->id,
        'transfer_type' => TransferType::RoleChange->value,
        'from_branch_id' => $sydney->id,
        'to_branch_id' => $sydney->id,
        'to_role' => RoleEnum::MANAGER->value,
    ]);
});

test('combined staff branch and role move writes one branch_change row', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.transfer', $staff), [
            'to_branch_id' => $melbourne->id,
            'roles' => [RoleEnum::MANAGER->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Move and promote.',
        ])
        ->assertRedirect();

    expect(UserTransferHistory::query()->where('user_id', $staff->id)->count())->toBe(1);

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $staff->id,
        'transfer_type' => TransferType::BranchChange->value,
        'to_branch_id' => $melbourne->id,
        'to_role' => RoleEnum::MANAGER->value,
    ]);
});

test('platform users cannot transfer and drivers cannot use the staff transfer route', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.transfer', $admin), [
            'to_branch_id' => $melbourne->id,
            'roles' => [RoleEnum::STAFF->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Invalid.',
        ])
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.transfer', $driver), [
            'to_branch_id' => $melbourne->id,
            'roles' => [RoleEnum::STAFF->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Invalid.',
        ])
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.transfer', $staff), [
            'to_branch_id' => $melbourne->id,
            'effective_date' => '2026-09-21',
            'reason' => 'Invalid.',
        ])
        ->assertNotFound();

    expect(UserTransferHistory::query()->count())->toBe(0)
        ->and($admin->fresh()->user_type)->toBe(UserType::PLATFORM)
        ->and($driver->fresh()->user_type)->toBe(UserType::DRIVER)
        ->and($staff->fresh()->user_type)->toBe(UserType::BRANCH);
});

test('staff transfer requires a destination role', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.branch-staff.show', $staff))
        ->post(route('platform.staff.transfer', $staff), [
            'to_branch_id' => $melbourne->id,
            'effective_date' => '2026-09-21',
            'reason' => 'Missing role.',
        ])
        ->assertRedirect(route('platform.branch-staff.show', $staff))
        ->assertSessionHasErrors('roles');

    expect($staff->fresh()->branch_id)->not->toBe($melbourne->id)
        ->and(UserTransferHistory::query()->where('user_id', $staff->id)->exists())->toBeFalse();
});

test('cannot transfer the last super-admin away from an active branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branchSuper = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.branch-staff.show', $branchSuper))
        ->post(route('platform.staff.transfer', $branchSuper), [
            'to_branch_id' => $melbourne->id,
            'roles' => [RoleEnum::SUPER_ADMIN->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Attempted last SA move.',
        ])
        ->assertRedirect(route('platform.branch-staff.show', $branchSuper))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => SuperAdmin::LAST_ACTIVE_BRANCH_MESSAGE,
        ]);

    expect($branchSuper->fresh()->branch_id)->not->toBe($melbourne->id)
        ->and(UserTransferHistory::query()->where('user_id', $branchSuper->id)->exists())->toBeFalse();
});

test('can transfer the last super-admin away after the branch is deactivated', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branchSuper = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $sydney->update(['status' => false]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.transfer', $branchSuper), [
            'to_branch_id' => $melbourne->id,
            'roles' => [RoleEnum::ADMIN->value],
            'effective_date' => '2026-09-21',
            'reason' => 'Branch closed.',
        ])
        ->assertRedirect();

    expect($branchSuper->fresh()->branch_id)->toBe($melbourne->id);

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $branchSuper->id,
        'from_branch_id' => $sydney->id,
        'to_branch_id' => $melbourne->id,
        'to_role' => RoleEnum::ADMIN->value,
    ]);
});

test('platform staff edit branch change writes transfer history', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branch-staff.update', $staff), [
            'branch_id' => $melbourne->id,
            'name' => $staff->name,
            'phone' => $staff->phone,
            'email' => $staff->email,
            'roles' => [RoleEnum::MANAGER->value],
            'is_active' => true,
        ])
        ->assertRedirect(route('platform.branch-staff.show', $staff));

    expect($staff->fresh()->branch_id)->toBe($melbourne->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($melbourne->id);
    expect($staff->fresh()->hasRole(RoleEnum::MANAGER->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->assertDatabaseHas('user_transfer_histories', [
        'user_id' => $staff->id,
        'subject_type' => TransferSubjectType::BranchStaff->value,
        'transfer_type' => TransferType::BranchChange->value,
        'from_branch_id' => $sydney->id,
        'to_branch_id' => $melbourne->id,
        'to_role' => RoleEnum::MANAGER->value,
        'reason' => 'Updated via staff edit',
    ]);
});
