<?php

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Database\Seeders\BranchInventorySeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ExtendedCategorySeeder;
use Database\Seeders\ExtendedProductSeeder;
use Database\Seeders\NationwideBranchSeeder;
use Database\Seeders\NationwideInventorySeeder;
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
        NationwideBranchSeeder::class,
        UserSeeder::class,
        CatalogSeeder::class,
        ExtendedCategorySeeder::class,
        ExtendedProductSeeder::class,
        BranchInventorySeeder::class,
        NationwideInventorySeeder::class,
    ]);
});

test('nationwide branches cover australia with local radii while core stores deliver anywhere', function () {
    $branches = Branch::query()->get();
    $sydney = $branches->firstWhere('slug', BranchSeeder::SYDNEY_CBD);
    $alice = $branches->firstWhere('slug', 'alice-springs-todd');

    expect($branches->count())->toBeGreaterThanOrEqual(60)
        ->and($branches->every(fn (Branch $branch): bool => (float) $branch->latitude <= -10.0
            && (float) $branch->latitude >= -44.0
            && (float) $branch->longitude >= 112.0
            && (float) $branch->longitude <= 154.0))->toBeTrue()
        ->and($sydney->delivery_radius_km)->toBeNull()
        ->and((float) $alice->delivery_radius_km)->toBe((float) NationwideBranchSeeder::RADIUS_REMOTE_KM);
});

test('extended catalog seeds 500+ priced products under hidden sub-categories', function () {
    $rollingTobacco = Category::query()->where('slug', 'rolling-tobacco')->firstOrFail();

    expect(Product::query()->count())->toBeGreaterThanOrEqual(500)
        ->and(Product::query()->whereNull('selling_price')->exists())->toBeFalse()
        ->and(Product::query()->doesntHave('categories')->exists())->toBeFalse()
        ->and($rollingTobacco->featured)->toBeFalse()
        ->and($rollingTobacco->show_in_nav)->toBeFalse()
        ->and($rollingTobacco->products()->count())->toBeGreaterThan(0);
});

test('nationwide inventory stocks every branch with a stock-in ledger and keeps hand-tuned stock', function () {
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $marlboro = Product::query()->where('slug', 'marlboro-gold-soft-pack-20s')->firstOrFail();
    $stockedUnits = (int) Inventory::query()->whereNull('variant_id')->sum('quantity');
    $ledgerUnits = (int) InventoryTransaction::query()
        ->whereNull('variant_id')
        ->where('type', InventoryTransactionType::StockIn)
        ->sum('quantity');

    expect(Branch::query()->whereDoesntHave('inventories')->exists())->toBeFalse()
        ->and(Inventory::query()->where('quantity', 0)->exists())->toBeTrue()
        ->and($ledgerUnits)->toBe($stockedUnits)
        ->and(Inventory::query()->where('branch_id', $sydney->id)->where('product_id', $marlboro->id)->whereNull('variant_id')->value('quantity'))->toBe(120);
});

test('re-running the nationwide seeders adds no duplicate rows', function () {
    $counts = fn (): array => [Branch::query()->count(), Product::query()->count(), Inventory::query()->count()];
    $before = $counts();

    $this->seed([NationwideBranchSeeder::class, ExtendedProductSeeder::class, NationwideInventorySeeder::class]);

    expect($counts())->toBe($before);
});
