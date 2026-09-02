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
    $admin = User::query()->where('email', 'platform@test.com')->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $uptown = Branch::query()->where('slug', 'uptown')->firstOrFail();

    $uptownStaff = User::factory()->create([
        'name' => 'Uptown Staff',
        'email' => 'uptown-staff@test.com',
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
            ->has('staff.data', 2)
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.index', ['branch_id' => $downtown->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/index')
            ->has('staff.data', 1)
            ->where('staff.data.0.email', 'branch@test.com')
        );

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.show', $uptownStaff))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/show')
            ->where('staffMember.email', 'uptown-staff@test.com')
            ->where('staffMember.branch.slug', 'uptown')
        );
});
