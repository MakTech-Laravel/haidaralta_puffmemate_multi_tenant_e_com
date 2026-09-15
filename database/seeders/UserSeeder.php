<?php

namespace Database\Seeders;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverProfile;
use App\Models\User;
use App\Services\Driver\DriverService;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public const PLATFORM_SUPER_ADMIN_PHONE = '+61411111111';

    public const PLATFORM_ADMIN_PHONE = '+61422222222';

    public const PLATFORM_MANAGER_PHONE = '+61433333333';

    public const BRANCH_1_SUPER_ADMIN_PHONE = '+61444444444';

    public const BRANCH_1_ADMIN_PHONE = '+61455555555';

    public const BRANCH_2_SUPER_ADMIN_PHONE = '+61466666666';

    public const BRANCH_2_ADMIN_PHONE = '+61477777777';

    public const BRANCH_3_SUPER_ADMIN_PHONE = '+61410101010';

    public const BRANCH_3_ADMIN_PHONE = '+61412121212';

    public const BRANCH_1_STAFF_PHONE = '+61413131313';

    public const BRANCH_2_STAFF_PHONE = '+61414141414';

    public const DRIVER_PHONE = '+61488888888';

    public const CUSTOMER_PHONE = '+61499999999';

    /** @deprecated Prefer *_PHONE constants for login. Kept for display/email lookups. */
    public const PLATFORM_SUPER_ADMIN = 'ava.mitchell@puffmemate.dev';

    public const PLATFORM_ADMIN = 'noah.patel@puffmemate.dev';

    public const PLATFORM_MANAGER = 'mia.reynolds@puffmemate.dev';

    public const BRANCH_1_SUPER_ADMIN = 'alex.chen@sydney-cbd.dev';

    public const BRANCH_1_ADMIN = 'sam.okonkwo@sydney-cbd.dev';

    public const BRANCH_2_SUPER_ADMIN = 'jordan.blake@melbourne-collins.dev';

    public const BRANCH_2_ADMIN = 'riley.nguyen@melbourne-collins.dev';

    public const BRANCH_3_SUPER_ADMIN = 'casey.martin@brisbane-queen.dev';

    public const BRANCH_3_ADMIN = 'chris.wong@brisbane-queen.dev';

    public const BRANCH_1_STAFF = 'priya.nair@sydney-cbd.dev';

    public const BRANCH_2_STAFF = 'liam.brooks@melbourne-collins.dev';

    public const DRIVER = 'daniel.foster@puffmemate.dev';

    public const CUSTOMER = 'emma.walker@example.com';

    public const DEFAULT_PASSWORD = 'password';

    /**
     * Seed portal login accounts. Canonical login is phone; password is "password".
     */
    public function run(): void
    {
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();

        $this->seedPlatformUsers();
        $this->seedBranchUsers($sydney, [
            [
                'phone' => self::BRANCH_1_SUPER_ADMIN_PHONE,
                'email' => self::BRANCH_1_SUPER_ADMIN,
                'name' => 'Alex Chen',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::BRANCH_1_ADMIN_PHONE,
                'email' => self::BRANCH_1_ADMIN,
                'name' => 'Sam Okonkwo',
                'role' => RoleEnum::ADMIN,
            ],
            [
                'phone' => self::BRANCH_1_STAFF_PHONE,
                'email' => self::BRANCH_1_STAFF,
                'name' => 'Priya Nair',
                'role' => RoleEnum::STAFF,
            ],
        ]);
        $this->seedBranchUsers($melbourne, [
            [
                'phone' => self::BRANCH_2_SUPER_ADMIN_PHONE,
                'email' => self::BRANCH_2_SUPER_ADMIN,
                'name' => 'Jordan Blake',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::BRANCH_2_ADMIN_PHONE,
                'email' => self::BRANCH_2_ADMIN,
                'name' => 'Riley Nguyen',
                'role' => RoleEnum::ADMIN,
            ],
            [
                'phone' => self::BRANCH_2_STAFF_PHONE,
                'email' => self::BRANCH_2_STAFF,
                'name' => 'Liam Brooks',
                'role' => RoleEnum::STAFF,
            ],
        ]);
        $this->seedBranchUsers($brisbane, [
            [
                'phone' => self::BRANCH_3_SUPER_ADMIN_PHONE,
                'email' => self::BRANCH_3_SUPER_ADMIN,
                'name' => 'Casey Martin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::BRANCH_3_ADMIN_PHONE,
                'email' => self::BRANCH_3_ADMIN,
                'name' => 'Chris Wong',
                'role' => RoleEnum::ADMIN,
            ],
        ]);

        $driver = $this->seedPortalUser(
            phone: self::DRIVER_PHONE,
            email: self::DRIVER,
            name: 'Daniel Foster',
            userType: UserType::DRIVER,
        );

        $this->seedDriverProfile($driver, $sydney, $melbourne);

        $this->seedPortalUser(
            phone: self::CUSTOMER_PHONE,
            email: self::CUSTOMER,
            name: 'Emma Walker',
            userType: UserType::CUSTOMER,
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    private function seedPlatformUsers(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach ([
            [
                'phone' => self::PLATFORM_SUPER_ADMIN_PHONE,
                'email' => self::PLATFORM_SUPER_ADMIN,
                'name' => 'Ava Mitchell',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::PLATFORM_ADMIN_PHONE,
                'email' => self::PLATFORM_ADMIN,
                'name' => 'Noah Patel',
                'role' => RoleEnum::ADMIN,
            ],
            [
                'phone' => self::PLATFORM_MANAGER_PHONE,
                'email' => self::PLATFORM_MANAGER,
                'name' => 'Mia Reynolds',
                'role' => RoleEnum::MANAGER,
            ],
        ] as $account) {
            $user = $this->seedPortalUser(
                phone: $account['phone'],
                email: $account['email'],
                name: $account['name'],
                userType: UserType::PLATFORM,
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    /**
     * @param  list<array{phone: string, email: string, name: string, role: RoleEnum}>  $accounts
     */
    private function seedBranchUsers(Branch $branch, array $accounts): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        foreach ($accounts as $account) {
            $user = $this->seedPortalUser(
                phone: $account['phone'],
                email: $account['email'],
                name: $account['name'],
                userType: UserType::BRANCH,
                branchId: $branch->id,
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    private function seedDriverProfile(User $driver, Branch $sydney, Branch $melbourne): void
    {
        DriverProfile::query()->updateOrCreate(
            ['user_id' => $driver->id],
            [
                'license_number' => 'DL-DF-001',
                'vehicle_type' => DriverVehicleType::Motorcycle,
                'availability_status' => DriverAvailabilityStatus::Available,
            ],
        );

        $driverService = app(DriverService::class);
        $driverService->assignToBranch($driver, $sydney, status: DriverAssignmentStatus::Active);
        $driverService->assignToBranch($driver, $melbourne, status: DriverAssignmentStatus::Active);
    }

    private function seedPortalUser(
        string $phone,
        string $email,
        string $name,
        UserType $userType,
        ?int $branchId = null,
    ): User {
        return User::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'name' => $name,
                'email' => $email,
                'password' => self::DEFAULT_PASSWORD,
                'user_type' => $userType,
                'branch_id' => $branchId,
                'is_active' => true,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ],
        );
    }
}
