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
            BranchSeeder::ADELAIDE_RUNDLE => [
                [
                    'name' => 'North Adelaide',
                    'address' => '90 O\'Connell Street, North Adelaide SA 5006',
                    'latitude' => -34.9065000,
                    'longitude' => 138.5958000,
                    'city' => 'Adelaide',
                    'state' => 'SA',
                    'postal_code' => '5006',
                    'notes' => 'North of river zone',
                ],
                [
                    'name' => 'Unley',
                    'address' => '180 Unley Road, Unley SA 5061',
                    'latitude' => -34.9502000,
                    'longitude' => 138.6075000,
                    'city' => 'Adelaide',
                    'state' => 'SA',
                    'postal_code' => '5061',
                    'notes' => 'Southern suburbs zone',
                ],
            ],
            BranchSeeder::GOLD_COAST_SURFERS => [
                [
                    'name' => 'Broadbeach',
                    'address' => '20 Victorialand Avenue, Broadbeach QLD 4218',
                    'latitude' => -28.0285000,
                    'longitude' => 153.4312000,
                    'city' => 'Gold Coast',
                    'state' => 'QLD',
                    'postal_code' => '4218',
                    'notes' => 'Beach strip zone',
                ],
                [
                    'name' => 'Southport',
                    'address' => '55 Nerang Street, Southport QLD 4215',
                    'latitude' => -27.9678000,
                    'longitude' => 153.4135000,
                    'city' => 'Gold Coast',
                    'state' => 'QLD',
                    'postal_code' => '4215',
                    'notes' => 'CBD adjacent zone',
                ],
            ],
            BranchSeeder::CANBERRA_CIVIC => [
                [
                    'name' => 'Braddon',
                    'address' => '10 Lonsdale Street, Braddon ACT 2612',
                    'latitude' => -35.2718000,
                    'longitude' => 149.1325000,
                    'city' => 'Canberra',
                    'state' => 'ACT',
                    'postal_code' => '2612',
                    'notes' => 'Inner north zone',
                ],
                [
                    'name' => 'Kingston',
                    'address' => '25 Giles Street, Kingston ACT 2604',
                    'latitude' => -35.3155000,
                    'longitude' => 149.1448000,
                    'city' => 'Canberra',
                    'state' => 'ACT',
                    'postal_code' => '2604',
                    'notes' => 'Foreshore zone',
                ],
            ],
            BranchSeeder::NEWCASTLE_HUNTER => [
                [
                    'name' => 'Hamilton',
                    'address' => '40 Beaumont Street, Hamilton NSW 2303',
                    'latitude' => -32.9235000,
                    'longitude' => 151.7478000,
                    'city' => 'Newcastle',
                    'state' => 'NSW',
                    'postal_code' => '2303',
                    'notes' => 'Inner west zone',
                ],
                [
                    'name' => 'Merewether',
                    'address' => '15 Ridge Street, Merewether NSW 2291',
                    'latitude' => -32.9458000,
                    'longitude' => 151.7502000,
                    'city' => 'Newcastle',
                    'state' => 'NSW',
                    'postal_code' => '2291',
                    'notes' => 'Coastal zone',
                ],
            ],
            BranchSeeder::PARRAMATTA_CHURCH => [
                [
                    'name' => 'Harris Park',
                    'address' => '30 Wigram Street, Harris Park NSW 2150',
                    'latitude' => -33.8225000,
                    'longitude' => 151.0078000,
                    'city' => 'Parramatta',
                    'state' => 'NSW',
                    'postal_code' => '2150',
                    'notes' => 'Station precinct zone',
                ],
                [
                    'name' => 'Westmead',
                    'address' => '12 Darcy Road, Westmead NSW 2145',
                    'latitude' => -33.8088000,
                    'longitude' => 150.9875000,
                    'city' => 'Parramatta',
                    'state' => 'NSW',
                    'postal_code' => '2145',
                    'notes' => 'Hospital corridor zone',
                ],
            ],
        ];
    }
}
