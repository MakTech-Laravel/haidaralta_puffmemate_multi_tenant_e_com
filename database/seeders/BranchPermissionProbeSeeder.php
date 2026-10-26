<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Models\Branch;
use App\Support\BranchPermissionProbe;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

/**
 * One role + user per branch permission (Sydney CBD team) for manual / Pest gate probing.
 *
 * Prerequisites: {@see RolesAndPermissionsSeeder}, {@see BranchSeeder}.
 * `php artisan db:seed --class=BranchPermissionProbeSeeder`
 *
 * Login: {@see BranchPermissionProbe::phoneFor} / email, password {@see BranchPermissionProbe::PASSWORD}.
 */
class BranchPermissionProbeSeeder extends Seeder
{
    use ReportsSeedProgress;

    public function run(): void
    {
        $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $permissions = PermissionEnum::forGuard(RolesAndPermissionsSeeder::BRANCH_GUARD);

        $this->withSeedProgress(
            'Branch permission probes',
            $permissions,
            function (PermissionEnum $permission) use ($branch): void {
                BranchPermissionProbe::ensureUser($branch, $permission);
            },
            fn (int $total): string => "{$total} branch probe users ready on {$branch->slug} (password: ".BranchPermissionProbe::PASSWORD.').',
        );
    }
}
