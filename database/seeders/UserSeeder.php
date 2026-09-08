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
    public const PLATFORM_SUPER_ADMIN = 'superadmin@dev.com';

    public const PLATFORM_ADMIN = 'admin@dev.com';

    public const PLATFORM_MANAGER = 'manage@dev.com';

    public const BRANCH_1_SUPER_ADMIN = 'branch1.superadmin@dev.com';

    public const BRANCH_1_ADMIN = 'branch1.admin@dev.com';

    public const BRANCH_2_SUPER_ADMIN = 'branch2.superadmin@dev.com';

    public const BRANCH_2_ADMIN = 'branch2.admin@dev.com';

    public const DRIVER = 'driver@dev.com';

    public const CUSTOMER = 'user@dev.com';

    /**
     * Seed portal login accounts. Password for each user is their email.
     */
    public function run(): void
    {
        $this->forgetLegacyAccounts();

        $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();
        $uptown = Branch::query()->where('slug', 'uptown')->firstOrFail();

        $this->seedPlatformUsers();
        $this->seedBranchUsers($downtown, [
            [
                'email' => self::BRANCH_1_SUPER_ADMIN,
                'name' => 'Branch 1 Super Admin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'email' => self::BRANCH_1_ADMIN,
                'name' => 'Branch 1 Admin',
                'role' => RoleEnum::ADMIN,
            ],
        ]);
        $this->seedBranchUsers($uptown, [
            [
                'email' => self::BRANCH_2_SUPER_ADMIN,
                'name' => 'Branch 2 Super Admin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'email' => self::BRANCH_2_ADMIN,
                'name' => 'Branch 2 Admin',
                'role' => RoleEnum::ADMIN,
            ],
        ]);

        $this->seedPortalUser(
            email: self::DRIVER,
            name: 'Driver',
            userType: UserType::DRIVER,
        );

        $this->seedPortalUser(
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
                'email' => self::PLATFORM_SUPER_ADMIN,
                'name' => 'Platform Super Admin',
                'role' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'email' => self::PLATFORM_ADMIN,
                'name' => 'Platform Admin',
                'role' => RoleEnum::ADMIN,
            ],
            [
                'email' => self::PLATFORM_MANAGER,
                'name' => 'Platform Manager',
                'role' => RoleEnum::MANAGER,
            ],
        ] as $account) {
            $user = $this->seedPortalUser(
                email: $account['email'],
                name: $account['name'],
                userType: UserType::PLATFORM,
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    /**
     * @param  list<array{email: string, name: string, role: RoleEnum}>  $accounts
     */
    private function seedBranchUsers(Branch $branch, array $accounts): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        foreach ($accounts as $account) {
            $user = $this->seedPortalUser(
                email: $account['email'],
                name: $account['name'],
                userType: UserType::BRANCH,
                branchId: $branch->id,
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    private function seedPortalUser(
        string $email,
        string $name,
        UserType $userType,
        ?int $branchId = null,
    ): User {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $email,
                'user_type' => $userType,
                'branch_id' => $branchId,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Drop Phase-early @test.com accounts so reseed does not leave duplicates.
     */
    private function forgetLegacyAccounts(): void
    {
        User::query()
            ->whereIn('email', [
                'platform@test.com',
                'branch@test.com',
                'driver@test.com',
                'customer@test.com',
            ])
            ->delete();
    }
}
