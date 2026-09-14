<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BranchSeeder extends Seeder
{
    public const SYDNEY_CBD = 'sydney-cbd';

    public const MELBOURNE_COLLINS = 'melbourne-collins';

    public const BRISBANE_QUEEN = 'brisbane-queen';

    private const LOGO = '/images/storefront/logo.png';

    /**
     * Seed realistic Australian retail branches and their team-scoped roles.
     */
    public function run(): void
    {
        $this->clearObsoleteSeedLogos();

        $logo = is_file(public_path(ltrim(self::LOGO, '/'))) ? self::LOGO : null;

        $branches = [
            [
                'slug' => self::SYDNEY_CBD,
                'name' => 'PuffOnMate Sydney CBD',
                'code' => 'SYD-CBD-01',
                'logo' => $logo,
                'phone' => '+61292671200',
                'email' => 'sydney.cbd@puffonmate.dev',
                'address' => 'Shop 14, 420 George Street, Sydney NSW 2000',
                'latitude' => -33.8708000,
                'longitude' => 151.2073000,
                'opening_time' => '09:00',
                'closing_time' => '21:00',
                'status' => true,
            ],
            [
                'slug' => self::MELBOURNE_COLLINS,
                'name' => 'PuffOnMate Melbourne Collins',
                'code' => 'MEL-COL-01',
                'logo' => $logo,
                'phone' => '+61396501240',
                'email' => 'melbourne.collins@puffonmate.dev',
                'address' => 'Level 1, 260 Collins Street, Melbourne VIC 3000',
                'latitude' => -37.8152000,
                'longitude' => 144.9655000,
                'opening_time' => '10:00',
                'closing_time' => '22:00',
                'status' => true,
            ],
            [
                'slug' => self::BRISBANE_QUEEN,
                'name' => 'PuffOnMate Brisbane Queen St',
                'code' => 'BNE-QST-01',
                'logo' => $logo,
                'phone' => '+61732218890',
                'email' => 'brisbane.queen@puffonmate.dev',
                'address' => 'G09, 150 Queen Street, Brisbane QLD 4000',
                'latitude' => -27.4698000,
                'longitude' => 153.0251000,
                'opening_time' => '09:30',
                'closing_time' => '20:30',
                'status' => true,
            ],
        ];

        $rolesSeeder = app(RolesAndPermissionsSeeder::class);

        foreach ($branches as $branchData) {
            $branch = Branch::query()->updateOrCreate(
                ['slug' => $branchData['slug']],
                $branchData,
            );

            $rolesSeeder->seedBranchRolesForBranch($branch);
        }
    }

    private function clearObsoleteSeedLogos(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $disk = Storage::disk('public');

        if ($disk->exists('branches')) {
            $disk->deleteDirectory('branches');
        }
    }
}
