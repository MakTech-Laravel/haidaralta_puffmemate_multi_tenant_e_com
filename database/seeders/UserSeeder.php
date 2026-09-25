<?php

namespace Database\Seeders;

use App\Enums\AustralianState;
use App\Enums\CustomerAddressType;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\CustomerAddress;
use App\Models\DriverProfile;
use App\Models\User;
use App\Services\Driver\DriverService;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public const PLATFORM_SUPER_ADMIN_PHONE = '+61411111111';

    public const PLATFORM_ADMIN_PHONE = '+61422222222';

    public const PLATFORM_MANAGER_PHONE = '+61433333333';

    public const BRANCH_1_SUPER_ADMIN_PHONE = '+61444444444';

    public const BRANCH_1_ADMIN_PHONE = '+61455555555';

    public const BRANCH_2_SUPER_ADMIN_PHONE = '+61466666666';

    public const BRANCH_2_ADMIN_PHONE = '+61477777777';

    public const BRANCH_3_SUPER_ADMIN_PHONE = '+61410101010';

    public const BRANCH_3_ADMIN_PHONE = '+61412121212';

    public const BRANCH_1_STAFF_PHONE = '+61413131313';

    public const BRANCH_2_STAFF_PHONE = '+61414141414';

    public const DRIVER_PHONE = '+61488888888';

    public const CUSTOMER_PHONE = '+61499999999';

    /** @deprecated Prefer *_PHONE constants for login. Kept for display/email lookups. */
    public const PLATFORM_SUPER_ADMIN = 'ava.mitchell@puffmemate.dev';

    public const PLATFORM_ADMIN = 'noah.patel@puffmemate.dev';

    public const PLATFORM_MANAGER = 'mia.reynolds@puffmemate.dev';

    public const BRANCH_1_SUPER_ADMIN = 'alex.chen@sydney-cbd.dev';

    public const BRANCH_1_ADMIN = 'sam.okonkwo@sydney-cbd.dev';

    public const BRANCH_2_SUPER_ADMIN = 'jordan.blake@melbourne-collins.dev';

    public const BRANCH_2_ADMIN = 'riley.nguyen@melbourne-collins.dev';

    public const BRANCH_3_SUPER_ADMIN = 'casey.martin@brisbane-queen.dev';

    public const BRANCH_3_ADMIN = 'chris.wong@brisbane-queen.dev';

    public const BRANCH_1_STAFF = 'priya.nair@sydney-cbd.dev';

    public const BRANCH_2_STAFF = 'liam.brooks@melbourne-collins.dev';

    public const DRIVER = 'daniel.foster@puffmemate.dev';

    public const CUSTOMER = 'emma.walker@example.com';

    public const DEFAULT_PASSWORD = 'password';

    /**
     * Seed portal login accounts. Canonical login is phone; password is "password".
     */
    public function run(): void
    {
        $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
        $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
        $brisbane = Branch::query()->where('slug', BranchSeeder::BRISBANE_QUEEN)->firstOrFail();
        $perth = Branch::query()->where('slug', BranchSeeder::PERTH_HAY)->firstOrFail();
        $adelaide = Branch::query()->where('slug', BranchSeeder::ADELAIDE_RUNDLE)->firstOrFail();
        $surfers = Branch::query()->where('slug', BranchSeeder::GOLD_COAST_SURFERS)->firstOrFail();
        $canberra = Branch::query()->where('slug', BranchSeeder::CANBERRA_CIVIC)->firstOrFail();
        $newcastle = Branch::query()->where('slug', BranchSeeder::NEWCASTLE_HUNTER)->firstOrFail();
        $parramatta = Branch::query()->where('slug', BranchSeeder::PARRAMATTA_CHURCH)->firstOrFail();

        $this->seedPlatformUsers();

        $this->seedBranchUsers($sydney, [
            ['phone' => self::BRANCH_1_SUPER_ADMIN_PHONE, 'email' => self::BRANCH_1_SUPER_ADMIN, 'name' => 'Alex Chen', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => self::BRANCH_1_ADMIN_PHONE, 'email' => self::BRANCH_1_ADMIN, 'name' => 'Sam Okonkwo', 'role' => RoleEnum::ADMIN],
            ['phone' => self::BRANCH_1_STAFF_PHONE, 'email' => self::BRANCH_1_STAFF, 'name' => 'Priya Nair', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430101001', 'email' => 'harper.cole@sydney-cbd.dev', 'name' => 'Harper Cole', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430101002', 'email' => 'owen.fitzgerald@sydney-cbd.dev', 'name' => 'Owen Fitzgerald', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430101003', 'email' => 'zara.malik@sydney-cbd.dev', 'name' => 'Zara Malik', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430101004', 'email' => 'benjamin.shaw@sydney-cbd.dev', 'name' => 'Benjamin Shaw', 'role' => RoleEnum::ADMIN],
        ]);

        $this->seedBranchUsers($melbourne, [
            ['phone' => self::BRANCH_2_SUPER_ADMIN_PHONE, 'email' => self::BRANCH_2_SUPER_ADMIN, 'name' => 'Jordan Blake', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => self::BRANCH_2_ADMIN_PHONE, 'email' => self::BRANCH_2_ADMIN, 'name' => 'Riley Nguyen', 'role' => RoleEnum::ADMIN],
            ['phone' => self::BRANCH_2_STAFF_PHONE, 'email' => self::BRANCH_2_STAFF, 'name' => 'Liam Brooks', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430202001', 'email' => 'isla.thompson@melbourne-collins.dev', 'name' => 'Isla Thompson', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430202002', 'email' => 'marcus.delgado@melbourne-collins.dev', 'name' => 'Marcus Delgado', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430202003', 'email' => 'nina.volkov@melbourne-collins.dev', 'name' => 'Nina Volkov', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($brisbane, [
            ['phone' => self::BRANCH_3_SUPER_ADMIN_PHONE, 'email' => self::BRANCH_3_SUPER_ADMIN, 'name' => 'Casey Martin', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => self::BRANCH_3_ADMIN_PHONE, 'email' => self::BRANCH_3_ADMIN, 'name' => 'Chris Wong', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430303001', 'email' => 'sophie.grant@brisbane-queen.dev', 'name' => 'Sophie Grant', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430303002', 'email' => 'ethan.park@brisbane-queen.dev', 'name' => 'Ethan Park', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430303003', 'email' => 'amelia.ruiz@brisbane-queen.dev', 'name' => 'Amelia Ruiz', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($perth, [
            ['phone' => '+61430404001', 'email' => 'tate.morrison@perth-hay.dev', 'name' => 'Tate Morrison', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '+61430404002', 'email' => 'lucia.bennett@perth-hay.dev', 'name' => 'Lucia Bennett', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430404003', 'email' => 'harvey.knox@perth-hay.dev', 'name' => 'Harvey Knox', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430404004', 'email' => 'freya.sutton@perth-hay.dev', 'name' => 'Freya Sutton', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($adelaide, [
            ['phone' => '+61430505001', 'email' => 'callum.reid@adelaide-rundle.dev', 'name' => 'Callum Reid', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '+61430505002', 'email' => 'jade.osullivan@adelaide-rundle.dev', 'name' => 'Jade O\'Sullivan', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430505003', 'email' => 'noah.carr@adelaide-rundle.dev', 'name' => 'Noah Carr', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430505004', 'email' => 'piper.hughes@adelaide-rundle.dev', 'name' => 'Piper Hughes', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($surfers, [
            ['phone' => '+61430606001', 'email' => 'dylan.hayes@gold-coast-surfers.dev', 'name' => 'Dylan Hayes', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '+61430606002', 'email' => 'keira.bond@gold-coast-surfers.dev', 'name' => 'Keira Bond', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430606003', 'email' => 'mason.quinn@gold-coast-surfers.dev', 'name' => 'Mason Quinn', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430606004', 'email' => 'ruby.ellis@gold-coast-surfers.dev', 'name' => 'Ruby Ellis', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($canberra, [
            ['phone' => '+61430707001', 'email' => 'helen.frost@canberra-civic.dev', 'name' => 'Helen Frost', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '+61430707002', 'email' => 'george.lambert@canberra-civic.dev', 'name' => 'George Lambert', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430707003', 'email' => 'clara.west@canberra-civic.dev', 'name' => 'Clara West', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($newcastle, [
            ['phone' => '+61430808001', 'email' => 'tom.bradley@newcastle-hunter.dev', 'name' => 'Tom Bradley', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '+61430808002', 'email' => 'emma.nash@newcastle-hunter.dev', 'name' => 'Emma Nash', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430808003', 'email' => 'leo.cameron@newcastle-hunter.dev', 'name' => 'Leo Cameron', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430808004', 'email' => 'ivy.patel@newcastle-hunter.dev', 'name' => 'Ivy Patel', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedBranchUsers($parramatta, [
            ['phone' => '+61430909001', 'email' => 'arjun.mehta@parramatta-church.dev', 'name' => 'Arjun Mehta', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => '+61430909002', 'email' => 'grace.liu@parramatta-church.dev', 'name' => 'Grace Liu', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61430909003', 'email' => 'seth.obrien@parramatta-church.dev', 'name' => 'Seth O\'Brien', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430909004', 'email' => 'maya.singh@parramatta-church.dev', 'name' => 'Maya Singh', 'role' => RoleEnum::STAFF],
            ['phone' => '+61430909005', 'email' => 'jack.torres@parramatta-church.dev', 'name' => 'Jack Torres', 'role' => RoleEnum::STAFF],
        ]);

        $this->seedDrivers([
            [
                'phone' => self::DRIVER_PHONE,
                'email' => self::DRIVER,
                'name' => 'Daniel Foster',
                'license' => 'DL-DF-001',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $sydney,
                'alt_branch' => $melbourne,
            ],
            [
                'phone' => '+61420101001',
                'email' => 'oscar.knight@puffmemate.dev',
                'name' => 'Oscar Knight',
                'license' => 'DL-OK-214',
                'vehicle' => DriverVehicleType::Bicycle,
                'branch' => $sydney,
            ],
            [
                'phone' => '+61420101002',
                'email' => 'tessa.moran@puffmemate.dev',
                'name' => 'Tessa Moran',
                'license' => 'DL-TM-088',
                'vehicle' => DriverVehicleType::Car,
                'branch' => $sydney,
            ],
            [
                'phone' => '+61420101003',
                'email' => 'kai.nguyen@puffmemate.dev',
                'name' => 'Kai Nguyen',
                'license' => 'DL-KN-441',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $melbourne,
            ],
            [
                'phone' => '+61420101004',
                'email' => 'bella.hart@puffmemate.dev',
                'name' => 'Bella Hart',
                'license' => 'DL-BH-119',
                'vehicle' => DriverVehicleType::Bicycle,
                'branch' => $melbourne,
            ],
            [
                'phone' => '+61420101005',
                'email' => 'ryan.cho@puffmemate.dev',
                'name' => 'Ryan Cho',
                'license' => 'DL-RC-307',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $brisbane,
            ],
            [
                'phone' => '+61420101006',
                'email' => 'nina.blake@puffmemate.dev',
                'name' => 'Nina Blake',
                'license' => 'DL-NB-552',
                'vehicle' => DriverVehicleType::Car,
                'branch' => $brisbane,
            ],
            [
                'phone' => '+61420101007',
                'email' => 'hugo.price@puffmemate.dev',
                'name' => 'Hugo Price',
                'license' => 'DL-HP-073',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $perth,
            ],
            [
                'phone' => '+61420101008',
                'email' => 'aria.wells@puffmemate.dev',
                'name' => 'Aria Wells',
                'license' => 'DL-AW-918',
                'vehicle' => DriverVehicleType::Bicycle,
                'branch' => $adelaide,
            ],
            [
                'phone' => '+61420101009',
                'email' => 'felix.drake@puffmemate.dev',
                'name' => 'Felix Drake',
                'license' => 'DL-FD-260',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $surfers,
            ],
            [
                'phone' => '+61420101010',
                'email' => 'olivia.kent@puffmemate.dev',
                'name' => 'Olivia Kent',
                'license' => 'DL-OK-634',
                'vehicle' => DriverVehicleType::Car,
                'branch' => $canberra,
            ],
            [
                'phone' => '+61420101011',
                'email' => 'miles.carter@puffmemate.dev',
                'name' => 'Miles Carter',
                'license' => 'DL-MC-147',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $newcastle,
            ],
            [
                'phone' => '+61420101012',
                'email' => 'sienna.park@puffmemate.dev',
                'name' => 'Sienna Park',
                'license' => 'DL-SP-829',
                'vehicle' => DriverVehicleType::Bicycle,
                'branch' => $parramatta,
            ],
            [
                'phone' => '+61420101013',
                'email' => 'jasper.lin@puffmemate.dev',
                'name' => 'Jasper Lin',
                'license' => 'DL-JL-405',
                'vehicle' => DriverVehicleType::Motorcycle,
                'branch' => $parramatta,
            ],
        ]);

        $this->seedCustomers([
            ['phone' => self::CUSTOMER_PHONE, 'email' => self::CUSTOMER, 'name' => 'Emma Walker'],
            ['phone' => '+61450101001', 'email' => 'james.whitfield@example.com', 'name' => 'James Whitfield'],
            ['phone' => '+61450101002', 'email' => 'olivia.chen@example.com', 'name' => 'Olivia Chen'],
            ['phone' => '+61450101003', 'email' => 'lucas.martin@example.com', 'name' => 'Lucas Martin'],
            ['phone' => '+61450101004', 'email' => 'charlotte.brown@example.com', 'name' => 'Charlotte Brown'],
            ['phone' => '+61450101005', 'email' => 'william.taylor@example.com', 'name' => 'William Taylor'],
            ['phone' => '+61450101006', 'email' => 'ava.anderson@example.com', 'name' => 'Ava Anderson'],
            ['phone' => '+61450101007', 'email' => 'henry.thomas@example.com', 'name' => 'Henry Thomas'],
            ['phone' => '+61450101008', 'email' => 'mia.jackson@example.com', 'name' => 'Mia Jackson'],
            ['phone' => '+61450101009', 'email' => 'elijah.white@example.com', 'name' => 'Elijah White'],
            ['phone' => '+61450101010', 'email' => 'sofia.harris@example.com', 'name' => 'Sofia Harris'],
            ['phone' => '+61450101011', 'email' => 'benjamin.clark@example.com', 'name' => 'Benjamin Clark'],
            ['phone' => '+61450101012', 'email' => 'amelia.lewis@example.com', 'name' => 'Amelia Lewis'],
            ['phone' => '+61450101013', 'email' => 'logan.walker@example.com', 'name' => 'Logan Walker'],
            ['phone' => '+61450101014', 'email' => 'harper.hall@example.com', 'name' => 'Harper Hall'],
            ['phone' => '+61450101015', 'email' => 'sebastian.allen@example.com', 'name' => 'Sebastian Allen'],
            ['phone' => '+61450101016', 'email' => 'evelyn.young@example.com', 'name' => 'Evelyn Young'],
            ['phone' => '+61450101017', 'email' => 'jack.king@example.com', 'name' => 'Jack King'],
            ['phone' => '+61450101018', 'email' => 'scarlett.wright@example.com', 'name' => 'Scarlett Wright'],
            ['phone' => '+61450101019', 'email' => 'leo.scott@example.com', 'name' => 'Leo Scott'],
            ['phone' => '+61450101020', 'email' => 'chloe.green@example.com', 'name' => 'Chloe Green'],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    private function seedPlatformUsers(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach ([
            ['phone' => self::PLATFORM_SUPER_ADMIN_PHONE, 'email' => self::PLATFORM_SUPER_ADMIN, 'name' => 'Ava Mitchell', 'role' => RoleEnum::SUPER_ADMIN],
            ['phone' => self::PLATFORM_ADMIN_PHONE, 'email' => self::PLATFORM_ADMIN, 'name' => 'Noah Patel', 'role' => RoleEnum::ADMIN],
            ['phone' => self::PLATFORM_MANAGER_PHONE, 'email' => self::PLATFORM_MANAGER, 'name' => 'Mia Reynolds', 'role' => RoleEnum::MANAGER],
            ['phone' => '+61460101001', 'email' => 'elena.vargas@puffmemate.dev', 'name' => 'Elena Vargas', 'role' => RoleEnum::ADMIN],
            ['phone' => '+61460101002', 'email' => 'owen.hart@puffmemate.dev', 'name' => 'Owen Hart', 'role' => RoleEnum::MANAGER],
            ['phone' => '+61460101003', 'email' => 'priya.desai@puffmemate.dev', 'name' => 'Priya Desai', 'role' => RoleEnum::MANAGER],
        ] as $account) {
            $user = $this->seedPortalUser(
                phone: $account['phone'],
                email: $account['email'],
                name: $account['name'],
                userType: UserType::PLATFORM,
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    /**
     * @param  list<array{phone: string, email: string, name: string, role: RoleEnum}>  $accounts
     */
    private function seedBranchUsers(Branch $branch, array $accounts): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

        foreach ($accounts as $account) {
            $user = $this->seedPortalUser(
                phone: $account['phone'],
                email: $account['email'],
                name: $account['name'],
                userType: UserType::BRANCH,
                branchId: $branch->id,
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    /**
     * @param  list<array{phone: string, email: string, name: string, license: string, vehicle: DriverVehicleType, branch: Branch, alt_branch?: Branch}>  $drivers
     */
    private function seedDrivers(array $drivers): void
    {
        $driverService = app(DriverService::class);

        foreach ($drivers as $row) {
            $driver = $this->seedPortalUser(
                phone: $row['phone'],
                email: $row['email'],
                name: $row['name'],
                userType: UserType::DRIVER,
            );

            DriverProfile::query()->updateOrCreate(
                ['user_id' => $driver->id],
                [
                    'license_number' => $row['license'],
                    'vehicle_type' => $row['vehicle'],
                    'availability_status' => DriverAvailabilityStatus::Available,
                    'address' => $row['branch']->address,
                    'notes' => 'Seeded courier for '.$row['branch']->name.'.',
                ],
            );

            $driverService->assignToBranch($driver, $row['branch'], status: DriverAssignmentStatus::Active);

            if (isset($row['alt_branch']) && ! (bool) config('drivers.single_active_branch')) {
                $driverService->assignToBranch($driver, $row['alt_branch'], status: DriverAssignmentStatus::Active);
            }
        }
    }

    /**
     * @param  list<array{phone: string, email: string, name: string}>  $customers
     */
    private function seedCustomers(array $customers): void
    {
        foreach ($customers as $customer) {
            $user = $this->seedPortalUser(
                phone: $customer['phone'],
                email: $customer['email'],
                name: $customer['name'],
                userType: UserType::CUSTOMER,
            );

            if ($customer['phone'] !== self::CUSTOMER_PHONE) {
                continue;
            }

            CustomerAddress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => CustomerAddressType::Shipping,
                ],
                [
                    'address' => '3 Boucaut Street',
                    'region' => AustralianState::NewSouthWales,
                    'city' => 'Sydney',
                    'zip' => '2000',
                ],
            );

            CustomerAddress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => CustomerAddressType::Billing,
                ],
                [
                    'name' => 'Emma Walker',
                    'address' => '3 Boucaut Street',
                    'region' => AustralianState::NewSouthWales,
                    'city' => 'Sydney',
                    'zip' => '2000',
                    'email' => self::CUSTOMER,
                    'phone' => self::CUSTOMER_PHONE,
                ],
            );
        }
    }

    private function seedPortalUser(
        string $phone,
        string $email,
        string $name,
        UserType $userType,
        ?int $branchId = null,
    ): User {
        return User::query()->updateOrCreate(
            ['phone' => $phone],
            [
                'name' => $name,
                'email' => $email,
                'password' => self::DEFAULT_PASSWORD,
                'user_type' => $userType,
                'branch_id' => $branchId,
                'is_active' => true,
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ],
        );
    }
}
