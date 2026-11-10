<?php

namespace App\Support;

use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\BranchPermissionProbeSeeder;
use Database\Seeders\UserSeeder;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates one dedicated branch-scoped role + user per branch permission for gate probing.
 *
 * Used by {@see BranchPermissionProbeSeeder} and Pest matrix tests.
 * Anchored to a single branch team (default Sydney CBD) so Spatie team permissions resolve.
 */
final class BranchPermissionProbe
{
    public const PASSWORD = UserSeeder::DEFAULT_PASSWORD;

    public const EMAIL_DOMAIN = 'puffmemate.dev';

    /**
     * Reserved E.164 prefix for branch probe phones (+61470800001 …).
     */
    public const PHONE_PREFIX = '+614708';

    public static function emailFor(PermissionEnum $permission): string
    {
        self::assertBranch($permission);

        return 'bprobe+'.$permission->value.'@'.self::EMAIL_DOMAIN;
    }

    public static function phoneFor(PermissionEnum $permission): string
    {
        self::assertBranch($permission);

        $index = array_search($permission, PermissionEnum::forGuard('branch'), true);

        if ($index === false) {
            throw new InvalidArgumentException("Unknown branch permission [{$permission->value}].");
        }

        return sprintf('%s%05d', self::PHONE_PREFIX, $index + 1);
    }

    public static function roleNameFor(PermissionEnum $permission): string
    {
        self::assertBranch($permission);

        return 'bprobe-'.$permission->value;
    }

    /**
     * @return list<string>
     */
    public static function permissionNamesFor(PermissionEnum $permission): array
    {
        self::assertBranch($permission);

        if ($permission === PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE) {
            return [
                PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE->value,
                PermissionEnum::MANAGE_BRANCH_ORDERS->value,
            ];
        }

        return [$permission->value];
    }

    /**
     * @param  list<PermissionEnum>|null  $onlyPermissions
     */
    public static function ensureUser(Branch $branch, PermissionEnum $permission, ?array $onlyPermissions = null): User
    {
        self::assertBranch($permission);

        $permissionNames = $onlyPermissions === null
            ? self::permissionNamesFor($permission)
            : array_values(array_unique(array_map(
                static function (PermissionEnum $case): string {
                    self::assertBranch($case);

                    return $case->value;
                },
                $onlyPermissions,
            )));

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($branch->id);

        try {
            $teamKey = config('permission.column_names.team_foreign_key');

            $role = Role::query()->firstOrCreate([
                'name' => self::roleNameFor($permission),
                'guard_name' => 'branch',
                $teamKey => $branch->id,
            ]);

            $role->syncPermissions($permissionNames);

            $user = User::query()->updateOrCreate(
                ['email' => self::emailFor($permission)],
                [
                    'name' => 'Branch Probe '.$permission->label(),
                    'phone' => self::phoneFor($permission),
                    'password' => self::PASSWORD,
                    'user_type' => UserType::BRANCH,
                    'branch_id' => $branch->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            if ((int) $user->branch_id !== (int) $branch->id) {
                $user->forceFill(['branch_id' => $branch->id])->save();
            }

            $user->syncRoles([$role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user->fresh();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    private static function assertBranch(PermissionEnum $permission): void
    {
        if ($permission->guard() !== 'branch') {
            throw new InvalidArgumentException(
                "BranchPermissionProbe only supports branch permissions, got [{$permission->value}].",
            );
        }
    }
}
