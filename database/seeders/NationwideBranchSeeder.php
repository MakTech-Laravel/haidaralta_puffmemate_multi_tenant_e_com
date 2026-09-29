<?php

namespace Database\Seeders;

use App\Models\Branch;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NationwideBranchSeeder extends Seeder
{
    use ReportsSeedProgress;
    use WithoutModelEvents;

    public const RADIUS_METRO_KM = 20;

    public const RADIUS_REGIONAL_KM = 60;

    public const RADIUS_REMOTE_KM = 250;

    private const LOGO = '/images/storefront/logo.png';

    /**
     * @var array<string, array{radius: int, max_active: int, handling_fee: string, cost_per_km: string, opening: string, closing: string}>
     */
    private const TIERS = [
        'metro' => ['radius' => self::RADIUS_METRO_KM, 'max_active' => 30, 'handling_fee' => '4.50', 'cost_per_km' => '0.35', 'opening' => '09:00', 'closing' => '21:00'],
        'regional' => ['radius' => self::RADIUS_REGIONAL_KM, 'max_active' => 15, 'handling_fee' => '6.50', 'cost_per_km' => '0.45', 'opening' => '09:00', 'closing' => '18:00'],
        'remote' => ['radius' => self::RADIUS_REMOTE_KM, 'max_active' => 8, 'handling_fee' => '9.50', 'cost_per_km' => '0.60', 'opening' => '08:30', 'closing' => '17:30'],
    ];

    /**
     * @var array<string, string>
     */
    private const STATE_AREA_CODES = [
        'NSW' => '2',
        'ACT' => '2',
        'VIC' => '3',
        'TAS' => '3',
        'QLD' => '7',
        'SA' => '8',
        'WA' => '8',
        'NT' => '8',
    ];

    /**
     * Seed the Australia-wide store network on top of the 9 core BranchSeeder stores.
     * Metro stores deliver locally, regional and remote stores cover wider areas, and the
     * core stores keep a null radius (deliver anywhere) so every address has a candidate.
     */
    public function run(): void
    {
        $logo = is_file(public_path(ltrim(self::LOGO, '/'))) ? self::LOGO : null;
        $rolesSeeder = app(RolesAndPermissionsSeeder::class);
        $branches = $this->branches();

        $this->startSeedProgress('Branches', count($branches));

        foreach ($branches as $index => $branchData) {
            $tier = self::TIERS[$branchData['tier']];

            $branch = Branch::query()->updateOrCreate(
                ['slug' => $branchData['slug']],
                [
                    'name' => 'PuffMeMate '.$branchData['name'],
                    'code' => $branchData['code'],
                    'logo' => $logo,
                    'phone' => $this->phoneFor($branchData['state'], $index),
                    'email' => str_replace('-', '.', $branchData['slug']).'@puffmemate.dev',
                    'address' => $branchData['address'],
                    'latitude' => $branchData['lat'],
                    'longitude' => $branchData['lng'],
                    'delivery_radius_km' => $tier['radius'],
                    'max_active_fulfillments' => $tier['max_active'],
                    'transfer_handling_fee' => $tier['handling_fee'],
                    'transfer_cost_per_km' => $tier['cost_per_km'],
                    'opening_time' => $tier['opening'],
                    'closing_time' => $tier['closing'],
                    'status' => true,
                ],
            );

            $rolesSeeder->seedBranchRolesForBranch($branch);
            $this->advanceSeedProgress();
        }

        $this->finishSeedProgress(sprintf(
            '%d nationwide branches ready (%d branches in total).',
            count($branches),
            Branch::query()->count(),
        ));
    }

    private function phoneFor(string $state, int $index): string
    {
        return sprintf('+61%s8%03d%04d', self::STATE_AREA_CODES[$state], 100 + $index, 2000 + ($index * 37));
    }

    /**
     * Real Australian retail locations (approximate storefront coordinates).
     *
     * @return list<array{slug: string, name: string, code: string, state: string, address: string, lat: float, lng: float, tier: string}>
     */
    private function branches(): array
    {
        return [
            // ——— NSW: Sydney metro ———
            ['slug' => 'bondi-junction', 'name' => 'Bondi Junction', 'code' => 'SYD-BDJ-01', 'state' => 'NSW', 'address' => 'Shop 3, 500 Oxford Street, Bondi Junction NSW 2022', 'lat' => -33.8914, 'lng' => 151.2503, 'tier' => 'metro'],
            ['slug' => 'chatswood-victoria', 'name' => 'Chatswood Victoria Ave', 'code' => 'SYD-CHW-01', 'state' => 'NSW', 'address' => 'Shop 18, 345 Victoria Avenue, Chatswood NSW 2067', 'lat' => -33.7969, 'lng' => 151.1822, 'tier' => 'metro'],
            ['slug' => 'liverpool-macquarie', 'name' => 'Liverpool Macquarie St', 'code' => 'SYD-LVP-01', 'state' => 'NSW', 'address' => 'Shop 7, 262 Macquarie Street, Liverpool NSW 2170', 'lat' => -33.9210, 'lng' => 150.9239, 'tier' => 'metro'],
            ['slug' => 'penrith-high', 'name' => 'Penrith High St', 'code' => 'SYD-PEN-01', 'state' => 'NSW', 'address' => 'Shop 21, 569 High Street, Penrith NSW 2750', 'lat' => -33.7510, 'lng' => 150.6942, 'tier' => 'metro'],
            ['slug' => 'blacktown-main', 'name' => 'Blacktown Main St', 'code' => 'SYD-BLK-01', 'state' => 'NSW', 'address' => 'Shop 4, 17 Main Street, Blacktown NSW 2148', 'lat' => -33.7710, 'lng' => 150.9063, 'tier' => 'metro'],
            ['slug' => 'hurstville-forest', 'name' => 'Hurstville Forest Rd', 'code' => 'SYD-HUR-01', 'state' => 'NSW', 'address' => 'Shop 9, 225 Forest Road, Hurstville NSW 2220', 'lat' => -33.9670, 'lng' => 151.1020, 'tier' => 'metro'],
            ['slug' => 'manly-corso', 'name' => 'Manly Corso', 'code' => 'SYD-MAN-01', 'state' => 'NSW', 'address' => 'Shop 2, 49 The Corso, Manly NSW 2095', 'lat' => -33.7990, 'lng' => 151.2860, 'tier' => 'metro'],
            ['slug' => 'castle-hill-towers', 'name' => 'Castle Hill Towers', 'code' => 'SYD-CAS-01', 'state' => 'NSW', 'address' => 'Shop 30, Castle Towers, 6 Castle Street, Castle Hill NSW 2154', 'lat' => -33.7307, 'lng' => 151.0036, 'tier' => 'metro'],
            ['slug' => 'bankstown-north-terrace', 'name' => 'Bankstown North Terrace', 'code' => 'SYD-BNK-01', 'state' => 'NSW', 'address' => 'Shop 11, 1 North Terrace, Bankstown NSW 2200', 'lat' => -33.9173, 'lng' => 151.0340, 'tier' => 'metro'],
            ['slug' => 'campbelltown-macarthur', 'name' => 'Campbelltown Macarthur', 'code' => 'SYD-CMB-01', 'state' => 'NSW', 'address' => 'Shop 16, Macarthur Square, 200 Gilchrist Drive, Campbelltown NSW 2560', 'lat' => -34.0735, 'lng' => 150.7962, 'tier' => 'metro'],

            // ——— NSW: regional ———
            ['slug' => 'wollongong-crown', 'name' => 'Wollongong Crown St', 'code' => 'NSW-WOL-01', 'state' => 'NSW', 'address' => 'Shop 6, 200 Crown Street, Wollongong NSW 2500', 'lat' => -34.4248, 'lng' => 150.8931, 'tier' => 'regional'],
            ['slug' => 'gosford-mann', 'name' => 'Gosford Mann St', 'code' => 'NSW-GOS-01', 'state' => 'NSW', 'address' => 'Shop 5, 150 Mann Street, Gosford NSW 2250', 'lat' => -33.4263, 'lng' => 151.3417, 'tier' => 'regional'],
            ['slug' => 'port-macquarie-horton', 'name' => 'Port Macquarie Horton St', 'code' => 'NSW-PMQ-01', 'state' => 'NSW', 'address' => 'Shop 4, 20 Horton Street, Port Macquarie NSW 2444', 'lat' => -31.4308, 'lng' => 152.9089, 'tier' => 'regional'],
            ['slug' => 'coffs-harbour-drive', 'name' => 'Coffs Harbour', 'code' => 'NSW-COF-01', 'state' => 'NSW', 'address' => 'Shop 10, 22 Harbour Drive, Coffs Harbour NSW 2450', 'lat' => -30.2963, 'lng' => 153.1135, 'tier' => 'regional'],
            ['slug' => 'tamworth-peel', 'name' => 'Tamworth Peel St', 'code' => 'NSW-TAM-01', 'state' => 'NSW', 'address' => 'Shop 3, 345 Peel Street, Tamworth NSW 2340', 'lat' => -31.0927, 'lng' => 150.9320, 'tier' => 'regional'],
            ['slug' => 'dubbo-macquarie', 'name' => 'Dubbo Macquarie St', 'code' => 'NSW-DUB-01', 'state' => 'NSW', 'address' => 'Shop 8, 140 Macquarie Street, Dubbo NSW 2830', 'lat' => -32.2493, 'lng' => 148.6012, 'tier' => 'regional'],
            ['slug' => 'wagga-wagga-baylis', 'name' => 'Wagga Wagga Baylis St', 'code' => 'NSW-WAG-01', 'state' => 'NSW', 'address' => 'Shop 12, 175 Baylis Street, Wagga Wagga NSW 2650', 'lat' => -35.1152, 'lng' => 147.3690, 'tier' => 'regional'],
            ['slug' => 'albury-dean', 'name' => 'Albury Dean St', 'code' => 'NSW-ALB-01', 'state' => 'NSW', 'address' => 'Shop 2, 555 Dean Street, Albury NSW 2640', 'lat' => -36.0808, 'lng' => 146.9160, 'tier' => 'regional'],
            ['slug' => 'orange-summer', 'name' => 'Orange Summer St', 'code' => 'NSW-ORA-01', 'state' => 'NSW', 'address' => 'Shop 6, 220 Summer Street, Orange NSW 2800', 'lat' => -33.2835, 'lng' => 149.1013, 'tier' => 'regional'],
            ['slug' => 'lismore-molesworth', 'name' => 'Lismore Molesworth St', 'code' => 'NSW-LIS-01', 'state' => 'NSW', 'address' => 'Shop 4, 90 Molesworth Street, Lismore NSW 2480', 'lat' => -28.8133, 'lng' => 153.2769, 'tier' => 'regional'],
            ['slug' => 'broken-hill-argent', 'name' => 'Broken Hill Argent St', 'code' => 'NSW-BKH-01', 'state' => 'NSW', 'address' => 'Shop 1, 350 Argent Street, Broken Hill NSW 2880', 'lat' => -31.9569, 'lng' => 141.4655, 'tier' => 'remote'],

            // ——— ACT ———
            ['slug' => 'belconnen-westfield', 'name' => 'Belconnen', 'code' => 'CBR-BEL-01', 'state' => 'ACT', 'address' => 'Shop 150, Westfield Belconnen, Benjamin Way, Belconnen ACT 2617', 'lat' => -35.2380, 'lng' => 149.0660, 'tier' => 'metro'],
            ['slug' => 'woden-westfield', 'name' => 'Woden', 'code' => 'CBR-WOD-01', 'state' => 'ACT', 'address' => 'Shop 88, Westfield Woden, Keltie Street, Phillip ACT 2606', 'lat' => -35.3450, 'lng' => 149.0880, 'tier' => 'metro'],

            // ——— VIC: Melbourne metro ———
            ['slug' => 'chadstone', 'name' => 'Chadstone', 'code' => 'MEL-CHD-01', 'state' => 'VIC', 'address' => 'Shop B120, 1341 Dandenong Road, Chadstone VIC 3148', 'lat' => -37.8862, 'lng' => 145.0830, 'tier' => 'metro'],
            ['slug' => 'box-hill-central', 'name' => 'Box Hill Central', 'code' => 'MEL-BXH-01', 'state' => 'VIC', 'address' => 'Shop 14, 1 Main Street, Box Hill VIC 3128', 'lat' => -37.8193, 'lng' => 145.1219, 'tier' => 'metro'],
            ['slug' => 'dandenong-lonsdale', 'name' => 'Dandenong Lonsdale St', 'code' => 'MEL-DAN-01', 'state' => 'VIC', 'address' => 'Shop 5, 250 Lonsdale Street, Dandenong VIC 3175', 'lat' => -37.9875, 'lng' => 145.2150, 'tier' => 'metro'],
            ['slug' => 'frankston-wells', 'name' => 'Frankston Wells St', 'code' => 'MEL-FRA-01', 'state' => 'VIC', 'address' => 'Shop 9, 40 Wells Street, Frankston VIC 3199', 'lat' => -38.1416, 'lng' => 145.1236, 'tier' => 'metro'],
            ['slug' => 'footscray-paisley', 'name' => 'Footscray Paisley St', 'code' => 'MEL-FOO-01', 'state' => 'VIC', 'address' => 'Shop 3, 60 Paisley Street, Footscray VIC 3011', 'lat' => -37.8000, 'lng' => 144.8990, 'tier' => 'metro'],
            ['slug' => 'st-kilda-acland', 'name' => 'St Kilda Acland St', 'code' => 'MEL-STK-01', 'state' => 'VIC', 'address' => 'Shop 2, 120 Acland Street, St Kilda VIC 3182', 'lat' => -37.8676, 'lng' => 144.9790, 'tier' => 'metro'],
            ['slug' => 'epping-pacific', 'name' => 'Epping Pacific', 'code' => 'MEL-EPP-01', 'state' => 'VIC', 'address' => 'Shop 44, Pacific Epping, 571 High Street, Epping VIC 3076', 'lat' => -37.6485, 'lng' => 145.0205, 'tier' => 'metro'],
            ['slug' => 'werribee-plaza', 'name' => 'Werribee Plaza', 'code' => 'MEL-WER-01', 'state' => 'VIC', 'address' => 'Shop 12, Pacific Werribee, 250 Derrimut Road, Hoppers Crossing VIC 3029', 'lat' => -37.8715, 'lng' => 144.6840, 'tier' => 'metro'],

            // ——— VIC: regional ———
            ['slug' => 'geelong-malop', 'name' => 'Geelong Malop St', 'code' => 'VIC-GEE-01', 'state' => 'VIC', 'address' => 'Shop 7, 150 Malop Street, Geelong VIC 3220', 'lat' => -38.1480, 'lng' => 144.3600, 'tier' => 'regional'],
            ['slug' => 'ballarat-sturt', 'name' => 'Ballarat Sturt St', 'code' => 'VIC-BAL-01', 'state' => 'VIC', 'address' => 'Shop 4, 45 Sturt Street, Ballarat VIC 3350', 'lat' => -37.5622, 'lng' => 143.8575, 'tier' => 'regional'],
            ['slug' => 'bendigo-hargreaves', 'name' => 'Bendigo Hargreaves St', 'code' => 'VIC-BEN-01', 'state' => 'VIC', 'address' => 'Shop 6, 70 Hargreaves Street, Bendigo VIC 3550', 'lat' => -36.7570, 'lng' => 144.2794, 'tier' => 'regional'],
            ['slug' => 'shepparton-maude', 'name' => 'Shepparton Maude St', 'code' => 'VIC-SHE-01', 'state' => 'VIC', 'address' => 'Shop 3, 200 Maude Street, Shepparton VIC 3630', 'lat' => -36.3833, 'lng' => 145.3990, 'tier' => 'regional'],
            ['slug' => 'mildura-langtree', 'name' => 'Mildura Langtree Ave', 'code' => 'VIC-MIL-01', 'state' => 'VIC', 'address' => 'Shop 5, 110 Langtree Avenue, Mildura VIC 3500', 'lat' => -34.1855, 'lng' => 142.1625, 'tier' => 'remote'],
            ['slug' => 'traralgon-franklin', 'name' => 'Traralgon Franklin St', 'code' => 'VIC-TRA-01', 'state' => 'VIC', 'address' => 'Shop 2, 40 Franklin Street, Traralgon VIC 3844', 'lat' => -38.1950, 'lng' => 146.5400, 'tier' => 'regional'],

            // ——— TAS ———
            ['slug' => 'hobart-elizabeth', 'name' => 'Hobart Elizabeth St', 'code' => 'TAS-HOB-01', 'state' => 'TAS', 'address' => 'Shop 7, 80 Elizabeth Street, Hobart TAS 7000', 'lat' => -42.8820, 'lng' => 147.3270, 'tier' => 'metro'],
            ['slug' => 'launceston-brisbane', 'name' => 'Launceston Brisbane St', 'code' => 'TAS-LST-01', 'state' => 'TAS', 'address' => 'Shop 5, 90 Brisbane Street, Launceston TAS 7250', 'lat' => -41.4380, 'lng' => 147.1370, 'tier' => 'regional'],
            ['slug' => 'devonport-rooke', 'name' => 'Devonport Rooke St', 'code' => 'TAS-DEV-01', 'state' => 'TAS', 'address' => 'Shop 2, 40 Rooke Street, Devonport TAS 7310', 'lat' => -41.1790, 'lng' => 146.3500, 'tier' => 'regional'],

            // ——— QLD: Brisbane, Gold Coast & Sunshine Coast ———
            ['slug' => 'chermside-westfield', 'name' => 'Chermside', 'code' => 'BNE-CHE-01', 'state' => 'QLD', 'address' => 'Shop 220, Westfield Chermside, Gympie Road, Chermside QLD 4032', 'lat' => -27.3856, 'lng' => 153.0310, 'tier' => 'metro'],
            ['slug' => 'fortitude-valley-brunswick', 'name' => 'Fortitude Valley', 'code' => 'BNE-FTV-01', 'state' => 'QLD', 'address' => 'Shop 4, 300 Brunswick Street, Fortitude Valley QLD 4006', 'lat' => -27.4570, 'lng' => 153.0350, 'tier' => 'metro'],
            ['slug' => 'logan-hyperdome', 'name' => 'Logan Hyperdome', 'code' => 'BNE-LOG-01', 'state' => 'QLD', 'address' => 'Shop 15, Logan Hyperdome, Pacific Highway, Shailer Park QLD 4128', 'lat' => -27.6570, 'lng' => 153.1760, 'tier' => 'metro'],
            ['slug' => 'ipswich-bell', 'name' => 'Ipswich Bell St', 'code' => 'BNE-IPS-01', 'state' => 'QLD', 'address' => 'Shop 8, 1 Bell Street, Ipswich QLD 4305', 'lat' => -27.6146, 'lng' => 152.7600, 'tier' => 'metro'],
            ['slug' => 'north-lakes-westfield', 'name' => 'North Lakes', 'code' => 'BNE-NLK-01', 'state' => 'QLD', 'address' => 'Shop 32, Westfield North Lakes, 1 North Lakes Drive, North Lakes QLD 4509', 'lat' => -27.2340, 'lng' => 153.0190, 'tier' => 'metro'],
            ['slug' => 'robina-town-centre', 'name' => 'Robina Town Centre', 'code' => 'GC-ROB-01', 'state' => 'QLD', 'address' => 'Shop 5, Robina Town Centre, 19 Robina Town Centre Drive, Robina QLD 4226', 'lat' => -28.0770, 'lng' => 153.3840, 'tier' => 'metro'],
            ['slug' => 'maroochydore-sunshine-plaza', 'name' => 'Maroochydore Sunshine Plaza', 'code' => 'SC-MAR-01', 'state' => 'QLD', 'address' => 'Shop 9, Sunshine Plaza, 154 Horton Parade, Maroochydore QLD 4558', 'lat' => -26.6560, 'lng' => 153.0890, 'tier' => 'metro'],

            // ——— QLD: regional ———
            ['slug' => 'toowoomba-ruthven', 'name' => 'Toowoomba Ruthven St', 'code' => 'QLD-TWB-01', 'state' => 'QLD', 'address' => 'Shop 11, 400 Ruthven Street, Toowoomba QLD 4350', 'lat' => -27.5620, 'lng' => 151.9530, 'tier' => 'regional'],
            ['slug' => 'bundaberg-bourbong', 'name' => 'Bundaberg Bourbong St', 'code' => 'QLD-BUN-01', 'state' => 'QLD', 'address' => 'Shop 3, 90 Bourbong Street, Bundaberg QLD 4670', 'lat' => -24.8660, 'lng' => 152.3490, 'tier' => 'regional'],
            ['slug' => 'rockhampton-east', 'name' => 'Rockhampton East St', 'code' => 'QLD-ROK-01', 'state' => 'QLD', 'address' => 'Shop 6, 200 East Street, Rockhampton QLD 4700', 'lat' => -23.3790, 'lng' => 150.5120, 'tier' => 'regional'],
            ['slug' => 'mackay-victoria', 'name' => 'Mackay Victoria St', 'code' => 'QLD-MKY-01', 'state' => 'QLD', 'address' => 'Shop 4, 80 Victoria Street, Mackay QLD 4740', 'lat' => -21.1420, 'lng' => 149.1860, 'tier' => 'regional'],
            ['slug' => 'townsville-flinders', 'name' => 'Townsville Flinders St', 'code' => 'QLD-TSV-01', 'state' => 'QLD', 'address' => 'Shop 10, 300 Flinders Street, Townsville QLD 4810', 'lat' => -19.2590, 'lng' => 146.8170, 'tier' => 'regional'],
            ['slug' => 'cairns-lake', 'name' => 'Cairns Lake St', 'code' => 'QLD-CNS-01', 'state' => 'QLD', 'address' => 'Shop 5, 60 Lake Street, Cairns QLD 4870', 'lat' => -16.9200, 'lng' => 145.7760, 'tier' => 'regional'],
            ['slug' => 'mount-isa-miles', 'name' => 'Mount Isa Miles St', 'code' => 'QLD-ISA-01', 'state' => 'QLD', 'address' => 'Shop 2, 25 Miles Street, Mount Isa QLD 4825', 'lat' => -20.7256, 'lng' => 139.4927, 'tier' => 'remote'],

            // ——— SA ———
            ['slug' => 'glenelg-jetty', 'name' => 'Glenelg Jetty Rd', 'code' => 'ADL-GLE-01', 'state' => 'SA', 'address' => 'Shop 6, 120 Jetty Road, Glenelg SA 5045', 'lat' => -34.9805, 'lng' => 138.5150, 'tier' => 'metro'],
            ['slug' => 'elizabeth-city-centre', 'name' => 'Elizabeth City Centre', 'code' => 'ADL-ELZ-01', 'state' => 'SA', 'address' => 'Shop 18, Elizabeth City Centre, 50 Elizabeth Way, Elizabeth SA 5112', 'lat' => -34.7160, 'lng' => 138.6700, 'tier' => 'metro'],
            ['slug' => 'marion-westfield', 'name' => 'Marion', 'code' => 'ADL-MAR-01', 'state' => 'SA', 'address' => 'Shop 40, Westfield Marion, 297 Diagonal Road, Oaklands Park SA 5046', 'lat' => -35.0140, 'lng' => 138.5480, 'tier' => 'metro'],
            ['slug' => 'mount-gambier-commercial', 'name' => 'Mount Gambier', 'code' => 'SA-MTG-01', 'state' => 'SA', 'address' => 'Shop 3, 20 Commercial Street West, Mount Gambier SA 5290', 'lat' => -37.8290, 'lng' => 140.7790, 'tier' => 'regional'],
            ['slug' => 'port-augusta-commercial', 'name' => 'Port Augusta', 'code' => 'SA-PTA-01', 'state' => 'SA', 'address' => 'Shop 2, 40 Commercial Road, Port Augusta SA 5700', 'lat' => -32.4930, 'lng' => 137.7650, 'tier' => 'remote'],

            // ——— WA ———
            ['slug' => 'fremantle-market', 'name' => 'Fremantle Market St', 'code' => 'PER-FRE-01', 'state' => 'WA', 'address' => 'Shop 4, 50 Market Street, Fremantle WA 6160', 'lat' => -32.0560, 'lng' => 115.7470, 'tier' => 'metro'],
            ['slug' => 'joondalup-lakeside', 'name' => 'Joondalup Lakeside', 'code' => 'PER-JDL-01', 'state' => 'WA', 'address' => 'Shop 22, Lakeside Joondalup, 420 Joondalup Drive, Joondalup WA 6027', 'lat' => -31.7440, 'lng' => 115.7660, 'tier' => 'metro'],
            ['slug' => 'cannington-carousel', 'name' => 'Cannington Carousel', 'code' => 'PER-CAN-01', 'state' => 'WA', 'address' => 'Shop 60, Westfield Carousel, 1382 Albany Highway, Cannington WA 6107', 'lat' => -32.0190, 'lng' => 115.9360, 'tier' => 'metro'],
            ['slug' => 'rockingham-centre', 'name' => 'Rockingham Centre', 'code' => 'PER-RKH-01', 'state' => 'WA', 'address' => 'Shop 9, Rockingham Centre, 1 Council Avenue, Rockingham WA 6168', 'lat' => -32.2800, 'lng' => 115.7340, 'tier' => 'metro'],
            ['slug' => 'mandurah-forum', 'name' => 'Mandurah Forum', 'code' => 'WA-MND-01', 'state' => 'WA', 'address' => 'Shop 5, Mandurah Forum, 330 Pinjarra Road, Mandurah WA 6210', 'lat' => -32.5340, 'lng' => 115.7470, 'tier' => 'regional'],
            ['slug' => 'bunbury-victoria', 'name' => 'Bunbury Victoria St', 'code' => 'WA-BUN-01', 'state' => 'WA', 'address' => 'Shop 3, 60 Victoria Street, Bunbury WA 6230', 'lat' => -33.3270, 'lng' => 115.6400, 'tier' => 'regional'],
            ['slug' => 'geraldton-marine', 'name' => 'Geraldton Marine Tce', 'code' => 'WA-GER-01', 'state' => 'WA', 'address' => 'Shop 4, 150 Marine Terrace, Geraldton WA 6530', 'lat' => -28.7770, 'lng' => 114.6150, 'tier' => 'regional'],
            ['slug' => 'kalgoorlie-hannan', 'name' => 'Kalgoorlie Hannan St', 'code' => 'WA-KAL-01', 'state' => 'WA', 'address' => 'Shop 2, 250 Hannan Street, Kalgoorlie WA 6430', 'lat' => -30.7490, 'lng' => 121.4660, 'tier' => 'remote'],
            ['slug' => 'karratha-city', 'name' => 'Karratha City', 'code' => 'WA-KAR-01', 'state' => 'WA', 'address' => 'Shop 6, Karratha City, Welcome Road, Karratha WA 6714', 'lat' => -20.7360, 'lng' => 116.8460, 'tier' => 'remote'],
            ['slug' => 'broome-carnarvon', 'name' => 'Broome Carnarvon St', 'code' => 'WA-BRM-01', 'state' => 'WA', 'address' => 'Shop 1, 12 Carnarvon Street, Broome WA 6725', 'lat' => -17.9560, 'lng' => 122.2410, 'tier' => 'remote'],

            // ——— NT ———
            ['slug' => 'darwin-smith-street', 'name' => 'Darwin Smith St Mall', 'code' => 'DRW-SMI-01', 'state' => 'NT', 'address' => 'Shop 3, 30 Smith Street Mall, Darwin NT 0800', 'lat' => -12.4630, 'lng' => 130.8420, 'tier' => 'metro'],
            ['slug' => 'palmerston-temple', 'name' => 'Palmerston', 'code' => 'DRW-PAL-01', 'state' => 'NT', 'address' => 'Shop 12, Palmerston Shopping Centre, 10 Temple Terrace, Palmerston NT 0830', 'lat' => -12.4800, 'lng' => 130.9840, 'tier' => 'metro'],
            ['slug' => 'katherine-terrace', 'name' => 'Katherine Terrace', 'code' => 'NT-KAT-01', 'state' => 'NT', 'address' => 'Shop 1, 20 Katherine Terrace, Katherine NT 0850', 'lat' => -14.4650, 'lng' => 132.2640, 'tier' => 'remote'],
            ['slug' => 'alice-springs-todd', 'name' => 'Alice Springs Todd Mall', 'code' => 'NT-ASP-01', 'state' => 'NT', 'address' => 'Shop 4, 70 Todd Mall, Alice Springs NT 0870', 'lat' => -23.6980, 'lng' => 133.8830, 'tier' => 'remote'],
        ];
    }
}
