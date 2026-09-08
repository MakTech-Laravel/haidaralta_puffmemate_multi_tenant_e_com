<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Seed sample branches and their team-scoped roles.
     */
    public function run(): void
    {
        $branches = [
            [
                'slug' => 'downtown',
                'name' => 'Downtown',
                'icon' => null,
                'address' => '123 Main Street, Downtown',
                'latitude' => 40.7128000,
                'longitude' => -74.0060000,
                'is_active' => true,
            ],
            [
                'slug' => 'uptown',
                'name' => 'Uptown',
                'icon' => null,
                'address' => '456 North Avenue, Uptown',
                'latitude' => 40.7831000,
                'longitude' => -73.9712000,
                'is_active' => true,
            ],
        ];

        $rolesSeeder = app(RolesAndPermissionsSeeder::class);

        foreach ($branches as $branchData) {
            $branch = Branch::firstOrCreate(
                ['slug' => $branchData['slug']],
                $branchData,
            );

            $rolesSeeder->seedBranchRolesForBranch($branch);
        }
    }
}
