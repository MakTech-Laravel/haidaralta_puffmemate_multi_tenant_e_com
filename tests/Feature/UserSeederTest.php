<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
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

test('branch seeder keeps five stores including ipswich brisbane street', function () {
    expect(Branch::query()->count())->toBe(5);

    $ipswich = Branch::query()->where('slug', BranchSeeder::IPSWICH_BRISBANE_ST)->firstOrFail();

    expect($ipswich->address)
        ->toContain('Brisbane Street')
        ->toContain('Ipswich');
});

test('each seeded branch has two drivers on the serial phone range', function () {
    $phones = [
        '+61488888888',
        '+61488888881',
        '+61488888882',
        '+61488888883',
        '+61488888884',
        '+61488888885',
        '+61488888886',
        '+61488888887',
        '+61488888889',
        '+61488888890',
    ];

    expect(User::query()->where('user_type', UserType::DRIVER)->whereIn('phone', $phones)->count())
        ->toBe(10)
        ->and(UserSeeder::DRIVER_PHONE)->toBe('+61488888888')
        ->and(UserSeeder::SECOND_DRIVER_PHONE)->toBe('+61488888881');

    foreach (Branch::query()->pluck('id') as $branchId) {
        expect(
            DriverBranchAssignment::query()->where('branch_id', $branchId)->count()
        )->toBe(2);
    }
});
