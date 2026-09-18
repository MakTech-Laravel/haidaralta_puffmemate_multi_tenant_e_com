<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function branchCreatePayload(array $overrides = []): array
{
    return [
        'name' => 'Midtown',
        'slug' => 'midtown',
        'code' => 'MID-001',
        'phone' => '0298765432',
        'email' => 'midtown@test.com',
        'address' => '789 Center St',
        'opening_time' => '09:00',
        'closing_time' => '21:00',
        'status' => true,
        'super_admin' => [
            'name' => 'Midtown Super Admin',
            'phone' => '0412000099',
            'email' => 'midtown.superadmin@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ],
        ...$overrides,
    ];
}

test('platform super admin can create a branch with its first super admin atomically', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $logo = UploadedFile::fake()->image('midtown.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), branchCreatePayload([
            'logo' => $logo,
        ]))
        ->assertRedirect(route('platform.branches.show', 'midtown'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch created with super-admin.',
        ]);

    $branch = Branch::query()->where('slug', 'midtown')->firstOrFail();

    expect($branch->name)->toBe('Midtown')
        ->and($branch->code)->toBe('MID-001')
        ->and($branch->phone)->toBe('+61298765432')
        ->and($branch->status)->toBeTrue()
        ->and($branch->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->logo);

    $superAdmin = User::query()->where('phone', '+61412000099')->firstOrFail();

    expect($superAdmin->user_type)->toBe(UserType::BRANCH)
        ->and($superAdmin->branch_id)->toBe($branch->id)
        ->and($superAdmin->email)->toBe('midtown.superadmin@test.com')
        ->and($superAdmin->phone_verified_at)->not->toBeNull();

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    expect($superAdmin->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', 'midtown'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('branch.slug', 'midtown')
            ->where('branch.needs_super_admin', false)
            ->missing('branch.super_admins')
            ->has('table.data', 1)
            ->where('table.data.0.phone', '+61412000099'));
});

test('creating a branch without a super admin payload is rejected and creates no branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = Branch::query()->count();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), [
            'name' => 'No Admin',
            'slug' => 'no-admin',
            'code' => 'NOA-001',
            'status' => true,
        ])
        ->assertSessionHasErrors(['super_admin']);

    expect(Branch::query()->count())->toBe($before)
        ->and(Branch::query()->where('slug', 'no-admin')->exists())->toBeFalse();
});

test('invalid super admin payload rolls back and creates no branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = Branch::query()->count();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), branchCreatePayload([
            'slug' => 'rollback-branch',
            'code' => 'ROL-001',
            'super_admin' => [
                'name' => 'Bad Admin',
                'phone' => 'not-a-phone',
                'email' => 'bad.admin@test.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        ]))
        ->assertSessionHasErrors(['super_admin.phone']);

    expect(Branch::query()->count())->toBe($before)
        ->and(Branch::query()->where('slug', 'rollback-branch')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'bad.admin@test.com')->exists())->toBeFalse();
});

test('recovery can add a super admin when a branch has none', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $branch = Branch::factory()->create([
        'name' => 'Orphan Store',
        'slug' => 'orphan-store',
        'code' => 'ORN-001',
        'status' => true,
    ]);

    (new RolesAndPermissionsSeeder)->seedBranchRolesForBranch($branch);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', $branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('branch.needs_super_admin', true));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.super-admin.create', $branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/create-super-admin')
            ->where('branch.slug', 'orphan-store'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.super-admin.store', $branch), [
            'name' => 'Orphan Super Admin',
            'phone' => '0412000088',
            'email' => 'orphan.superadmin@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.branches.show', 'orphan-store'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch super-admin created.',
        ]);

    $superAdmin = User::query()->where('phone', '+61412000088')->firstOrFail();

    expect($superAdmin->branch_id)->toBe($branch->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    expect($superAdmin->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
});

test('cannot create a second branch super admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.super-admin.store', $downtown), [
            'name' => 'Another Super',
            'phone' => '0412000098',
            'email' => 'another.super@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.branches.show', $downtown))
        ->assertInertiaFlash('toast', [
            'type' => 'warning',
            'message' => 'This branch already has a super-admin.',
        ]);

    expect(User::query()->where('phone', '+61412000098')->exists())->toBeFalse();
});

test('platform staff without create branches cannot create a branch', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->post(route('platform.branches.store'), branchCreatePayload([
            'name' => 'Blocked',
            'slug' => 'blocked',
            'code' => 'BLK-001',
        ]))
        ->assertForbidden();
});

test('cannot delete a branch that still has staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.branches.destroy', $downtown))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Cannot delete a branch that still has staff accounts.',
        ]);

    expect(Branch::query()->whereKey($downtown->id)->exists())->toBeTrue();
});

test('platform admin can update a branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'code' => 'OLD-001',
        'logo' => null,
    ]);

    $logo = UploadedFile::fake()->image('new-name.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.update', $branch), [
            'name' => 'New Name',
            'slug' => 'new-name',
            'code' => 'NEW-001',
            'logo' => $logo,
            'phone' => '0387654321',
            'opening_time' => '08:00',
            'closing_time' => '20:00',
            'status' => true,
        ])
        ->assertRedirect(route('platform.branches.show', 'new-name'));

    $branch->refresh();

    expect($branch->name)->toBe('New Name')
        ->and($branch->slug)->toBe('new-name')
        ->and($branch->code)->toBe('NEW-001')
        ->and($branch->phone)->toBe('+61387654321')
        ->and($branch->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->logo);
});

test('invalid branch contact phone is rejected', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), branchCreatePayload([
            'name' => 'Bad Phone',
            'slug' => 'bad-phone',
            'code' => 'BAD-001',
            'phone' => 'not-a-phone',
            'status' => true,
        ]))
        ->assertSessionHasErrors('phone');
});

test('platform can filter branches by inactive status and still open inactive branches', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $inactive = Branch::factory()->create([
        'name' => 'Closed Store',
        'slug' => 'closed-store',
        'code' => 'CLS-001',
        'status' => false,
    ]);

    (new RolesAndPermissionsSeeder)->seedBranchRolesForBranch($inactive);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.index', [
            'filter' => ['status' => 'inactive'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/index')
            ->where('table.filters.status', 'inactive')
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === 'closed-store',
            ))
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', $inactive))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('branch.status', false)
        );
});

test('platform branch show lists only that branch staff in a paginated table', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $uptown = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $other = User::factory()->create([
        'name' => 'Other Branch Clerk',
        'user_type' => UserType::BRANCH,
        'branch_id' => $uptown->id,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($uptown->id);
    $other->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $downtownCount = User::query()
        ->where('user_type', UserType::BRANCH)
        ->where('branch_id', $downtown->id)
        ->count();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', $downtown))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->missing('branch.super_admins')
            ->has('table.data', $downtownCount)
            ->where('table.meta.total', $downtownCount)
            ->has('table.links')
            ->has('table.filterOptions.roles')
            ->where('table.data', fn ($staff) => collect($staff)->every(
                fn ($row) => $row['name'] !== 'Other Branch Clerk',
            ))
            ->where('table.data', fn ($staff) => collect($staff)->contains(
                fn ($row) => $row['email'] === UserSeeder::BRANCH_1_SUPER_ADMIN,
            ))
        );
});

test('platform branch show can search staff by name', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $match = User::factory()->create([
        'name' => 'Zelda Branch Finder',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
    ]);

    User::factory()->create([
        'name' => 'Other Branch Noise',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $match->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', [
            $downtown,
            'filter' => ['search' => 'Zelda Branch'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('table.filters.search', 'Zelda Branch')
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Zelda Branch Finder')
        );
});

test('platform branch show can filter staff by role and status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $inactive = User::factory()->create([
        'name' => 'Inactive Queen Clerk',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
        'is_active' => false,
    ]);

    $activeStaff = User::factory()->create([
        'name' => 'Active Queen Clerk',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $inactive->assignRole(RoleEnum::STAFF->value);
    $activeStaff->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', [
            $downtown,
            'filter' => ['role' => RoleEnum::STAFF->value],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('table.filters.role', RoleEnum::STAFF->value)
            ->where('table.data', fn ($staff) => collect($staff)->contains(
                fn ($row) => $row['name'] === 'Active Queen Clerk',
            ))
            ->where('table.data', fn ($staff) => collect($staff)->every(
                fn ($row) => in_array(RoleEnum::STAFF->value, $row['roles'], true),
            ))
            ->where('table.data', fn ($staff) => collect($staff)->every(
                fn ($row) => $row['email'] !== UserSeeder::BRANCH_1_SUPER_ADMIN,
            ))
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', [
            $downtown,
            'filter' => ['is_active' => 'inactive'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('table.filters.is_active', 'inactive')
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Inactive Queen Clerk')
        );
});
