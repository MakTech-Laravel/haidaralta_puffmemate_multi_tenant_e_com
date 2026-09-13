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

test('platform super admin can create a branch then add super admin in step two', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $logo = UploadedFile::fake()->image('midtown.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), [
            'name' => 'Midtown',
            'slug' => 'midtown',
            'code' => 'MID-001',
            'logo' => $logo,
            'phone' => '0298765432',
            'email' => 'midtown@test.com',
            'address' => '789 Center St',
            'opening_time' => '09:00',
            'closing_time' => '21:00',
            'status' => true,
        ])
        ->assertRedirect(route('platform.branches.super-admin.create', 'midtown'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch created. Add a super-admin to finish setup.',
        ]);

    $branch = Branch::query()->where('slug', 'midtown')->firstOrFail();

    expect($branch->name)->toBe('Midtown')
        ->and($branch->code)->toBe('MID-001')
        ->and($branch->phone)->toBe('+61298765432')
        ->and($branch->status)->toBeTrue()
        ->and($branch->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->logo);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.super-admin.create', 'midtown'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/create-super-admin')
            ->where('branch.slug', 'midtown'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.super-admin.store', 'midtown'), [
            'name' => 'Midtown Super Admin',
            'phone' => '0412000099',
            'email' => 'midtown.superadmin@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.branches.show', 'midtown'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch super-admin created.',
        ]);

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
            ->has('branch.super_admins', 1)
            ->where('branch.super_admins.0.phone', '+61412000099'));
});

test('cannot create a second branch super admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

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
        ->post(route('platform.branches.store'), [
            'name' => 'Blocked',
            'slug' => 'blocked',
            'code' => 'BLK-001',
        ])
        ->assertForbidden();
});

test('cannot delete a branch that still has staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

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
        ->post(route('platform.branches.store'), [
            'name' => 'Bad Phone',
            'slug' => 'bad-phone',
            'code' => 'BAD-001',
            'phone' => 'not-a-phone',
            'status' => true,
        ])
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
        ->get(route('platform.branches.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/index')
            ->where('filters.status', 'inactive')
            ->where('branches.data', fn ($rows) => collect($rows)->contains(
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
