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
            UserSeeder::class,
            ApplicationSettingsSeeder::class,
            CatalogSeeder::class,
            BranchInventorySeeder::class,
            OrderConversationSeeder::class,
            ConversationVolumeSeeder::class,
        ]);
    }
}
