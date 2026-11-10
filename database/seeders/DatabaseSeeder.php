<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Throwable;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Order/placement seeders issue real mailables; keep SMTP (e.g. Mailtrap) off during seed.
        Config::set('mail.default', 'array');

        $this->call([
            RolesAndPermissionsSeeder::class,
            BranchSeeder::class,
            // NationwideBranchSeeder / NationwideInventorySeeder: opt-in via
            // `php artisan db:seed --class=NationwideBranchSeeder` (adds ~74 extra stores).
            BranchAreaSeeder::class,
            UserSeeder::class,
            ApplicationSettingsSeeder::class,
            MapProviderSeeder::class,
            CmsPageSeeder::class,
            HomeBannerSeeder::class,
            CategorySeeder::class,
            ExtendedCategorySeeder::class,
            ProductSeeder::class,
            // ExtendedProductSeeder::class,
            PriceRangeSeeder::class,
            CheckoutSettingsSeeder::class,
            DeliveryShiftSeeder::class,
            BranchInventorySeeder::class,
            DriverStockAssignmentSeeder::class,
            WorkforceShiftSeeder::class,
            TransferHistorySeeder::class,
            CustomerOrderSeeder::class,
        ]);

        $this->reindexSearch();
    }

    /**
     * WithoutModelEvents keeps Scout from indexing while seeding, so a fresh seed would leave the
     * Meilisearch indexes pointing at the previous run's ids (wrong products on category pages).
     * Rebuild them synchronously (no queue worker needed) once all catalog data exists.
     */
    private function reindexSearch(): void
    {
        if (config('scout.driver') !== 'meilisearch') {
            return;
        }

        $queue = config('scout.queue');
        Config::set('scout.queue', false);

        try {
            Artisan::call('scout:import-all', ['--fresh' => true]);
        } catch (Throwable $exception) {
            $this->command?->warn('Search index was not rebuilt: '.$exception->getMessage());
        } finally {
            Config::set('scout.queue', $queue);
        }
    }
}
