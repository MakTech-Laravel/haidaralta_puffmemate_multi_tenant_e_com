<?php

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\DriverInventory;
use App\Models\DriverInventoryTransaction;
use App\Models\DriverStockAssignment;
use App\Models\User;
use Database\Seeders\BranchInventorySeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DriverStockAssignmentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'images.unsplash.com/*' => Http::response(
            file_get_contents(public_path('images/storefront/logo.png')),
            200,
            ['Content-Type' => 'image/png'],
        ),
    ]);

    $this->seed([
        RolesAndPermissionsSeeder::class,
        BranchSeeder::class,
        UserSeeder::class,
        CatalogSeeder::class,
        BranchInventorySeeder::class,
    ]);
});

test('driver stock assignment seeder loads starter bags onto active drivers', function () {
    $this->seed(DriverStockAssignmentSeeder::class);

    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    expect(DriverStockAssignment::query()->count())->toBeGreaterThanOrEqual(3)
        ->and(DriverInventory::query()
            ->where('user_id', $driver->id)
            ->where('branch_id', $sydney->id)
            ->sum('quantity'))->toBeGreaterThan(0)
        ->and(DriverInventoryTransaction::query()
            ->where('type', InventoryTransactionType::DriverAssign)
            ->where('user_id', $driver->id)
            ->exists())->toBeTrue();
});

test('re-running driver stock assignment seeder does not duplicate inventory', function () {
    $this->seed(DriverStockAssignmentSeeder::class);

    $counts = fn (): array => [
        DriverStockAssignment::query()->count(),
        DriverInventory::query()->count(),
        (int) DriverInventory::query()->sum('quantity'),
    ];

    $before = $counts();

    $this->seed(DriverStockAssignmentSeeder::class);

    expect($counts())->toBe($before);
});
