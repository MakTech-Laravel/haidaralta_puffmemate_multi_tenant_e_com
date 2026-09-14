<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform admin can view branch staff across branches and filter by branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $uptown = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $seededBranchStaffCount = User::query()
        ->where('user_type', UserType::BRANCH)
        ->count();

    $uptownStaff = User::factory()->create([
        'name' => 'Melbourne Floor Staff',
        'email' => 'melbourne-staff@test.com',
        'user_type' => UserType::BRANCH,
        'branch_id' => $uptown->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($uptown->id);
    $uptownStaff->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/index')
            ->has('table.data', $seededBranchStaffCount + 1)
        );

    $downtownStaffCount = User::query()
        ->where('user_type', UserType::BRANCH)
        ->where('branch_id', $downtown->id)
        ->count();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.index', [
            'filter' => ['branch_id' => $downtown->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/index')
            ->has('table.data', $downtownStaffCount)
            ->where('table.data', fn ($staff) => collect($staff)->contains(
                fn ($row) => $row['email'] === UserSeeder::BRANCH_1_SUPER_ADMIN,
            ))
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.show', $uptownStaff))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/show')
            ->where('staffMember.email', 'melbourne-staff@test.com')
            ->where('staffMember.branch.slug', BranchSeeder::MELBOURNE_COLLINS)
        );
});

test('platform branch staff index can search by name', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $uptown = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $match = User::factory()->create([
        'name' => 'Zelda Branch Finder',
        'user_type' => UserType::BRANCH,
        'branch_id' => $uptown->id,
    ]);

    User::factory()->create([
        'name' => 'Other Branch Noise',
        'user_type' => UserType::BRANCH,
        'branch_id' => $uptown->id,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($uptown->id);
    $match->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.index', [
            'filter' => ['search' => 'Zelda Branch'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/index')
            ->where('table.filters.search', 'Zelda Branch')
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Zelda Branch Finder')
        );
});
