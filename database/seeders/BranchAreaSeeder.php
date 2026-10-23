<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchArea;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class BranchAreaSeeder extends Seeder
{
    use ReportsSeedProgress;

    public const SYDNEY_SURRY_HILLS = 'Surry Hills';

    public const SYDNEY_PADDINGTON = 'Paddington';

    public const SYDNEY_POTTS_POINT = 'Potts Point';

    public const MELBOURNE_SOUTH_YARRA = 'South Yarra';

    public const MELBOURNE_RICHMOND = 'Richmond';

    public const MELBOURNE_FITZROY = 'Fitzroy';

    /**
     * Seed delivery zones (areas) per branch. Idempotent by (branch_id, name).
     */
    public function run(): void
    {
        $catalog = $this->areaCatalog();
        $total = collect($catalog)->sum(fn (array $areas): int => count($areas));

        $this->startSeedProgress('Areas', $total);

        foreach ($catalog as $branchSlug => $areas) {
            $branch = Branch::query()->where('slug', $branchSlug)->first();

            if ($branch === null) {
                $this->advanceSeedProgress(count($areas));

                continue;
            }

            foreach ($areas as $area) {
                BranchArea::query()->updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'name' => $area['name'],
                    ],
                    [
                        'address' => $area['address'],
                        'latitude' => $area['latitude'],
                        'longitude' => $area['longitude'],
                        'city' => $area['city'],
                        'state' => $area['state'],
                        'postal_code' => $area['postal_code'],
                        'notes' => $area['notes'],
                        'is_active' => true,
                    ],
                );

                $this->advanceSeedProgress();
            }
        }

        $this->finishSeedProgress();
    }

    /**
     * @return array<string, list<array{
     *     name: string,
     *     address: string,
     *     latitude: float,
     *     longitude: float,
     *     city: string,
     *     state: string,
     *     postal_code: string,
     *     notes: string
     * }>>
     */
    private function areaCatalog(): array
    {
        return [
            BranchSeeder::SYDNEY_CBD => [
                [
                    'name' => self::SYDNEY_SURRY_HILLS,
                    'address' => '100 Crown Street, Surry Hills NSW 2010',
                    'latitude' => -33.8845000,
                    'longitude' => 151.2140000,
                    'city' => 'Sydney',
                    'state' => 'NSW',
                    'postal_code' => '2010',
                    'notes' => 'Inner city delivery zone',
                ],
                [
                    'name' => self::SYDNEY_PADDINGTON,
                    'address' => '250 Oxford Street, Paddington NSW 2021',
                    'latitude' => -33.8848000,
                    'longitude' => 151.2265000,
                    'city' => 'Sydney',
                    'state' => 'NSW',
                    'postal_code' => '2021',
                    'notes' => 'Eastern fringe zone',
                ],
                [
                    'name' => self::SYDNEY_POTTS_POINT,
                    'address' => '45 Macleay Street, Potts Point NSW 2011',
                    'latitude' => -33.8695000,
                    'longitude' => 151.2252000,
                    'city' => 'Sydney',
                    'state' => 'NSW',
                    'postal_code' => '2011',
                    'notes' => 'Harbour-side zone',
                ],
            ],
            BranchSeeder::MELBOURNE_COLLINS => [
                [
                    'name' => self::MELBOURNE_SOUTH_YARRA,
                    'address' => '12 Toorak Road, South Yarra VIC 3141',
                    'latitude' => -37.8385000,
                    'longitude' => 144.9920000,
                    'city' => 'Melbourne',
                    'state' => 'VIC',
                    'postal_code' => '3141',
                    'notes' => 'South bank residential zone',
                ],
                [
                    'name' => self::MELBOURNE_RICHMOND,
                    'address' => '220 Bridge Road, Richmond VIC 3121',
                    'latitude' => -37.8198000,
                    'longitude' => 144.9985000,
                    'city' => 'Melbourne',
                    'state' => 'VIC',
                    'postal_code' => '3121',
                    'notes' => 'Inner east zone',
                ],
                [
                    'name' => self::MELBOURNE_FITZROY,
                    'address' => '180 Brunswick Street, Fitzroy VIC 3065',
                    'latitude' => -37.7989000,
                    'longitude' => 144.9786000,
                    'city' => 'Melbourne',
                    'state' => 'VIC',
                    'postal_code' => '3065',
                    'notes' => 'Northside zone',
                ],
            ],
            BranchSeeder::BRISBANE_QUEEN => [
                [
                    'name' => 'Fortitude Valley',
                    'address' => '80 Brunswick Street, Fortitude Valley QLD 4006',
                    'latitude' => -27.4575000,
                    'longitude' => 153.0340000,
                    'city' => 'Brisbane',
                    'state' => 'QLD',
                    'postal_code' => '4006',
                    'notes' => 'Valley nightlife corridor',
                ],
                [
                    'name' => 'South Brisbane',
                    'address' => '15 Grey Street, South Brisbane QLD 4101',
                    'latitude' => -27.4752000,
                    'longitude' => 153.0178000,
                    'city' => 'Brisbane',
                    'state' => 'QLD',
                    'postal_code' => '4101',
                    'notes' => 'South Bank zone',
                ],
            ],
            BranchSeeder::PERTH_HAY => [
                [
                    'name' => 'Northbridge',
                    'address' => '50 Lake Street, Northbridge WA 6003',
                    'latitude' => -31.9488000,
                    'longitude' => 115.8570000,
                    'city' => 'Perth',
                    'state' => 'WA',
                    'postal_code' => '6003',
                    'notes' => 'Inner north zone',
                ],
                [
                    'name' => 'West Perth',
                    'address' => '12 Outram Street, West Perth WA 6005',
                    'latitude' => -31.9482000,
                    'longitude' => 115.8425000,
                    'city' => 'Perth',
                    'state' => 'WA',
                    'postal_code' => '6005',
                    'notes' => 'Business district fringe',
                ],
            ],
            BranchSeeder::IPSWICH_BRISBANE_ST => [
                [
                    'name' => 'Ipswich CBD',
                    'address' => '20 Bell Street, Ipswich QLD 4305',
                    'latitude' => -27.6128000,
                    'longitude' => 152.7602000,
                    'city' => 'Ipswich',
                    'state' => 'QLD',
                    'postal_code' => '4305',
                    'notes' => 'Town centre zone',
                ],
                [
                    'name' => 'Booval',
                    'address' => '85 Brisbane Road, Booval QLD 4304',
                    'latitude' => -27.6095000,
                    'longitude' => 152.7898000,
                    'city' => 'Ipswich',
                    'state' => 'QLD',
                    'postal_code' => '4304',
                    'notes' => 'Eastern corridor zone',
                ],
            ],
        ];
    }
}
