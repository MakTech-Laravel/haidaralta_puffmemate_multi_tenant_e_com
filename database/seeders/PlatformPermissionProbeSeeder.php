<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Support\PlatformPermissionProbe;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

/**
 * One role + user per platform permission for manual / Pest gate probing.
 *
 * Prerequisites: {@see RolesAndPermissionsSeeder} (permissions must exist).
 * Not part of {@see DatabaseSeeder} — run alone:
 * `php artisan db:seed --class=PlatformPermissionProbeSeeder`
 *
 * Login: phone {@see PlatformPermissionProbe::phoneFor} or email {@see PlatformPermissionProbe::emailFor},
 * password {@see PlatformPermissionProbe::PASSWORD} (`password`).
 */
class PlatformPermissionProbeSeeder extends Seeder
{
    use ReportsSeedProgress;

    public function run(): void
    {
        $permissions = PermissionEnum::forGuard(RolesAndPermissionsSeeder::PLATFORM_GUARD);

        $this->withSeedProgress(
            'Platform permission probes',
            $permissions,
            function (PermissionEnum $permission): void {
                PlatformPermissionProbe::ensureUser($permission);
            },
            fn (int $total): string => "{$total} platform probe users ready (password: ".PlatformPermissionProbe::PASSWORD.').',
        );
    }
}
