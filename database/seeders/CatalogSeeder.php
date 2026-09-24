<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Convenience wrapper: CategorySeeder then ProductSeeder.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
