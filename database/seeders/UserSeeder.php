<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Seed one test user per portal type.
     */
    public function run(): void
    {
        $downtownBranch = Branch::query()->where('slug', 'downtown')->firstOrFail();

        $platformUser = User::query()->updateOrCreate(
            ['email' => 'platform@test.com'],
            [
                'name' => 'Platform Super Admin',
                'password' => 'password',
                'user_type' => 'platform',
                'branch_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $platformUser->assignRole('super-admin');

        $branchUser = User::query()->updateOrCreate(
            ['email' => 'branch@test.com'],
            [
                'name' => 'Branch Super Admin',
                'password' => 'password',
                'user_type' => 'branch',
                'branch_id' => $downtownBranch->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($downtownBranch->id);
        $branchUser->assignRole('super-admin');

        User::query()->updateOrCreate(
            ['email' => 'driver@test.com'],
            [
                'name' => 'Driver Test User',
                'password' => 'password',
                'user_type' => 'driver',
                'branch_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@test.com'],
            [
                'name' => 'Customer Test User',
                'password' => 'password',
                'user_type' => 'customer',
                'branch_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
