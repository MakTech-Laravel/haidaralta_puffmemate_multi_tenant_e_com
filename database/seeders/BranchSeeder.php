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
                'code' => 'DTN-001',
                'logo' => null,
                'phone' => '+1-212-555-0100',
                'email' => 'downtown@dev.com',
                'address' => '123 Main Street, Downtown',
                'latitude' => 40.7128000,
                'longitude' => -74.0060000,
                'opening_time' => '09:00',
                'closing_time' => '21:00',
                'status' => true,
            ],
            [
                'slug' => 'uptown',
                'name' => 'Uptown',
                'code' => 'UPT-001',
                'logo' => null,
                'phone' => '+1-212-555-0200',
                'email' => 'uptown@dev.com',
                'address' => '456 North Avenue, Uptown',
                'latitude' => 40.7831000,
                'longitude' => -73.9712000,
                'opening_time' => '10:00',
                'closing_time' => '22:00',
                'status' => true,
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
