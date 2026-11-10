<?php

namespace App\Support;

use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PlatformPermissionProbeSeeder;
use Database\Seeders\UserSeeder;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates one dedicated platform role + user per platform permission for gate probing.
 *
 * Used by {@see PlatformPermissionProbeSeeder} and Pest matrix tests.
 * Credentials are documented on {@see self::PASSWORD} / email / phone helpers — do not replace Ava/Noah/Mia.
 */
final class PlatformPermissionProbe
{
    public const PASSWORD = UserSeeder::DEFAULT_PASSWORD;

    public const EMAIL_DOMAIN = 'puffmemate.dev';

    /**
     * Reserved E.164 prefix for probe phones (+61470900001 …) — avoids UserSeeder collisions.
     */
    public const PHONE_PREFIX = '+614709';

    public static function emailFor(PermissionEnum $permission): string
    {
        self::assertPlatform($permission);

        return 'probe+'.$permission->value.'@'.self::EMAIL_DOMAIN;
    }

    public static function phoneFor(PermissionEnum $permission): string
    {
        self::assertPlatform($permission);

        $index = array_search($permission, PermissionEnum::forGuard('platform'), true);

        if ($index === false) {
            throw new InvalidArgumentException("Unknown platform permission [{$permission->value}].");
        }

        return sprintf('%s%05d', self::PHONE_PREFIX, $index + 1);
    }

    public static function roleNameFor(PermissionEnum $permission): string
    {
        self::assertPlatform($permission);

        return 'probe-'.$permission->value;
    }

    /**
     * Permissions synced onto the probe role for the given primary permission.
     *
     * @return list<string>
     */
    public static function permissionNamesFor(PermissionEnum $permission): array
    {
        self::assertPlatform($permission);

        if ($permission === PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE) {
            return [
                PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE->value,
                PermissionEnum::MANAGE_ORDERS->value,
            ];
        }

        return [$permission->value];
    }

    /**
     * Create or refresh the probe role + user for one platform permission.
     *
     * @param  list<PermissionEnum>  $onlyPermissions  When set, sync exactly these (bypass default pairing).
     */
    public static function ensureUser(PermissionEnum $permission, ?array $onlyPermissions = null): User
    {
        self::assertPlatform($permission);

        $permissionNames = $onlyPermissions === null
            ? self::permissionNamesFor($permission)
            : array_values(array_unique(array_map(
                static function (PermissionEnum $case): string {
                    self::assertPlatform($case);

                    return $case->value;
                },
                $onlyPermissions,
            )));

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

        try {
            $teamKey = config('permission.column_names.team_foreign_key');

            $role = Role::query()->firstOrCreate([
                'name' => self::roleNameFor($permission),
                'guard_name' => 'platform',
                $teamKey => PlatformTeamResolver::PLATFORM_TEAM_ID,
            ]);

            $role->syncPermissions($permissionNames);

            $user = User::query()->updateOrCreate(
                ['email' => self::emailFor($permission)],
                [
                    'name' => 'Probe '.$permission->label(),
                    'phone' => self::phoneFor($permission),
                    'password' => self::PASSWORD,
                    'user_type' => UserType::PLATFORM,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user->fresh();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    private static function assertPlatform(PermissionEnum $permission): void
    {
        if ($permission->guard() !== 'platform') {
            throw new InvalidArgumentException(
                "PlatformPermissionProbe only supports platform permissions, got [{$permission->value}].",
            );
        }
    }
}
