<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
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

    public const DRIVER_PHONE = '+61488888888';

    public const CUSTOMER_PHONE = '+61499999999';

    /** @deprecated Prefer *_PHONE constants for login. Kept for display/email lookups. */
    public const PLATFORM_SUPER_ADMIN = 'superadmin@dev.com';

    public const PLATFORM_ADMIN = 'admin@dev.com';

    public const PLATFORM_MANAGER = 'manage@dev.com';

    public const BRANCH_1_SUPER_ADMIN = 'branch1.superadmin@dev.com';

    public const BRANCH_1_ADMIN = 'branch1.admin@dev.com';

    public const BRANCH_2_SUPER_ADMIN = 'branch2.superadmin@dev.com';

    public const BRANCH_2_ADMIN = 'branch2.admin@dev.com';

    public const DRIVER = 'driver@dev.com';

    public const CUSTOMER = 'user@dev.com';

    public const DEFAULT_PASSWORD = 'password';

    /**
     * Seed portal login accounts. Canonical login is phone; password is "password".
     */
    public function run(): void
    {
        $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();
        $uptown = Branch::query()->where('slug', 'uptown')->firstOrFail();

        $this->seedPlatformUsers();
        $this->seedBranchUsers($downtown, [
            [
                'phone' => self::BRANCH_1_SUPER_ADMIN_PHONE,
                'email' => self::BRANCH_1_SUPER_ADMIN,
                'name' => 'Branch 1 Super Admin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::BRANCH_1_ADMIN_PHONE,
                'email' => self::BRANCH_1_ADMIN,
                'name' => 'Branch 1 Admin',
                'role' => RoleEnum::ADMIN,
            ],
        ]);
        $this->seedBranchUsers($uptown, [
            [
                'phone' => self::BRANCH_2_SUPER_ADMIN_PHONE,
                'email' => self::BRANCH_2_SUPER_ADMIN,
                'name' => 'Branch 2 Super Admin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::BRANCH_2_ADMIN_PHONE,
                'email' => self::BRANCH_2_ADMIN,
                'name' => 'Branch 2 Admin',
                'role' => RoleEnum::ADMIN,
            ],
        ]);

        $this->seedPortalUser(
            phone: self::DRIVER_PHONE,
            email: self::DRIVER,
            name: 'Driver',
            userType: UserType::DRIVER,
        );

        $this->seedPortalUser(
            phone: self::CUSTOMER_PHONE,
            email: self::CUSTOMER,
            name: 'Customer',
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
                'name' => 'Platform Super Admin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'phone' => self::PLATFORM_ADMIN_PHONE,
                'email' => self::PLATFORM_ADMIN,
                'name' => 'Platform Admin',
                'role' => RoleEnum::ADMIN,
            ],
            [
                'phone' => self::PLATFORM_MANAGER_PHONE,
                'email' => self::PLATFORM_MANAGER,
                'name' => 'Platform Manager',
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
