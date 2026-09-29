<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            BranchSeeder::class,
            NationwideBranchSeeder::class,
            UserSeeder::class,
            ApplicationSettingsSeeder::class,
            CmsPageSeeder::class,
            HomeBannerSeeder::class,
            CategorySeeder::class,
            ExtendedCategorySeeder::class,
            ProductSeeder::class,
            ExtendedProductSeeder::class,
            PriceRangeSeeder::class,
            CheckoutSettingsSeeder::class,
            BranchInventorySeeder::class,
            NationwideInventorySeeder::class,
            WorkforceShiftSeeder::class,
            TransferHistorySeeder::class,
        ]);
    }
}
