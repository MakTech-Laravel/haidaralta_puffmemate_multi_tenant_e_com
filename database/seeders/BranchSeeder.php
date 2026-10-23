<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Services\Media\MediaUploadService;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BranchSeeder extends Seeder
{
    use ReportsSeedProgress;

    public const SYDNEY_CBD = 'sydney-cbd';

    public const MELBOURNE_COLLINS = 'melbourne-collins';

    public const BRISBANE_QUEEN = 'brisbane-queen';

    public const PERTH_HAY = 'perth-hay';

    public const IPSWICH_BRISBANE_ST = 'ipswich-brisbane-st';

    private const LOGO = '/images/storefront/logo.png';

    /**
     * Seed realistic Australian retail branches and their team-scoped roles.
     */
    public function run(): void
    {
        $logo = is_file(public_path(ltrim(self::LOGO, '/'))) ? self::LOGO : null;

        $branches = [
            [
                'slug' => self::SYDNEY_CBD,
                'name' => 'PuffMeMate Sydney CBD',
                'code' => 'SYD-CBD-01',
                'phone' => '+61292671200',
                'email' => 'sydney.cbd@puffmemate.dev',
                'address' => 'Shop 14, 420 George Street, Sydney NSW 2000',
                'latitude' => -33.8708000,
                'longitude' => 151.2073000,
                'fulfillment_priority' => 1,
                'opening_time' => '09:00',
                'closing_time' => '21:00',
                'status' => true,
            ],
            [
                'slug' => self::MELBOURNE_COLLINS,
                'name' => 'PuffMeMate Melbourne Collins',
                'code' => 'MEL-COL-01',
                'phone' => '+61396501240',
                'email' => 'melbourne.collins@puffmemate.dev',
                'address' => 'Level 1, 260 Collins Street, Melbourne VIC 3000',
                'latitude' => -37.8152000,
                'longitude' => 144.9655000,
                'opening_time' => '10:00',
                'closing_time' => '22:00',
                'status' => true,
            ],
            [
                'slug' => self::BRISBANE_QUEEN,
                'name' => 'PuffMeMate Brisbane Queen St',
                'code' => 'BNE-QST-01',
                'phone' => '+61732218890',
                'email' => 'brisbane.queen@puffmemate.dev',
                'address' => 'G09, 150 Queen Street, Brisbane QLD 4000',
                'latitude' => -27.4698000,
                'longitude' => 153.0251000,
                'opening_time' => '09:30',
                'closing_time' => '20:30',
                'status' => true,
            ],
            [
                'slug' => self::PERTH_HAY,
                'name' => 'PuffMeMate Perth Hay Street',
                'code' => 'PER-HAY-01',
                'phone' => '+61892218840',
                'email' => 'perth.hay@puffmemate.dev',
                'address' => 'Shop 3, 720 Hay Street, Perth WA 6000',
                'latitude' => -31.9539000,
                'longitude' => 115.8575000,
                'opening_time' => '09:00',
                'closing_time' => '20:00',
                'status' => true,
            ],
            [
                'slug' => self::IPSWICH_BRISBANE_ST,
                'name' => 'PuffMeMate Ipswich Brisbane St',
                'code' => 'IPS-BRS-01',
                'phone' => '+61732811220',
                'email' => 'ipswich@puffmemate.dev',
                'address' => 'Shop 5, 48 Brisbane Street, Ipswich QLD 4305, Australia',
                'latitude' => -27.6145000,
                'longitude' => 152.7609000,
                'opening_time' => '09:00',
                'closing_time' => '20:00',
                'status' => true,
            ],
        ];

        $rolesSeeder = app(RolesAndPermissionsSeeder::class);

        $this->withSeedProgress('Branches', $branches, function (array $branchData) use ($rolesSeeder, $logo): void {
            $branch = Branch::query()->updateOrCreate(
                ['slug' => $branchData['slug']],
                $branchData,
            );

            $this->attachLogo($branch, $logo);
            $rolesSeeder->seedBranchRolesForBranch($branch);
        }, sprintf('%d core branches ready.', count($branches)));
    }

    private function attachLogo(Branch $branch, ?string $logo): void
    {
        if ($logo === null) {
            return;
        }

        $absolute = public_path(ltrim($logo, '/'));

        if (! is_file($absolute)) {
            return;
        }

        $alreadyAttached = $branch->getMedia('logo')
            ->contains(fn (Media $media): bool => $media->getCustomProperty('seed_source') === $logo);

        if ($alreadyAttached) {
            return;
        }

        $media = app(MediaUploadService::class)->attachFromPath($branch, 'logo', $absolute);
        $media?->setCustomProperty('seed_source', $logo)->save();
    }
}
