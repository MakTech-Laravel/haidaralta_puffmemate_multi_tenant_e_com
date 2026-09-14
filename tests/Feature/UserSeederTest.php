<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('user seeder creates platform, branch, driver, and customer accounts', function () {
    expect(User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::PLATFORM);

    expect(User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::PLATFORM);

    expect(User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::PLATFORM);

    expect(User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::BRANCH);

    expect(User::query()->where('email', UserSeeder::BRANCH_2_ADMIN)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::BRANCH);

    expect(User::query()->where('email', UserSeeder::BRANCH_3_SUPER_ADMIN)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::BRANCH);

    expect(User::query()->where('email', UserSeeder::DRIVER)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::DRIVER);

    expect(User::query()->where('email', UserSeeder::CUSTOMER)->first())
        ->not->toBeNull()
        ->user_type->toBe(UserType::CUSTOMER);
});

test('seeded account passwords use the shared default password', function (string $email) {
    $user = User::query()->where('email', $email)->firstOrFail();

    expect(Hash::check(UserSeeder::DEFAULT_PASSWORD, $user->password))->toBeTrue();
})->with([
    UserSeeder::PLATFORM_SUPER_ADMIN,
    UserSeeder::PLATFORM_ADMIN,
    UserSeeder::PLATFORM_MANAGER,
    UserSeeder::BRANCH_1_SUPER_ADMIN,
    UserSeeder::BRANCH_1_ADMIN,
    UserSeeder::BRANCH_2_SUPER_ADMIN,
    UserSeeder::BRANCH_2_ADMIN,
    UserSeeder::BRANCH_3_SUPER_ADMIN,
    UserSeeder::BRANCH_3_ADMIN,
    UserSeeder::DRIVER,
    UserSeeder::CUSTOMER,
]);

test('platform and branch roles are assigned on the correct teams', function () {
    $platform = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $platformAdmin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $platformManager = User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail();
    $branch1 = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch2 = User::query()->where('email', UserSeeder::BRANCH_2_ADMIN)->firstOrFail();

    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    expect($platform->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
    expect($platformAdmin->hasRole(RoleEnum::ADMIN->value))->toBeTrue();
    expect($platformManager->hasRole(RoleEnum::MANAGER->value))->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch1->branch_id);
    expect($branch1->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch2->branch_id);
    expect($branch2->hasRole(RoleEnum::ADMIN->value))->toBeTrue();
});
