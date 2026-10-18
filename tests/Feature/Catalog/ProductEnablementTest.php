<?php

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use App\Services\Inventory\InventoryService;
use App\Support\Storefront\StorefrontCatalog;

test('platform product create lists the product at every branch', function () {
    $category = Category::factory()->create();
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    app(ProductService::class)->createFromPlatform([
        'category_ids' => [$category->id],
        'name' => 'Classic Pack',
        'slug' => 'classic-pack',
        'sku' => 'SKU-CLASSIC',
        'status' => true,
    ]);

    expect(collect(StorefrontCatalog::products($branchA))->pluck('id'))->toContain('classic-pack')
        ->and(collect(StorefrontCatalog::products($branchB))->pluck('id'))->toContain('classic-pack');
});

test('branch product create lists the product at other branches too', function () {
    $category = Category::factory()->create();
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    app(ProductService::class)->createFromBranch($branchA, [
        'category_ids' => [$category->id],
        'name' => 'Branch Pack',
        'slug' => 'branch-pack',
        'sku' => 'SKU-BRANCH',
        'status' => true,
    ]);

    expect(collect(StorefrontCatalog::products($branchB))->pluck('id'))->toContain('branch-pack');
});

test('inactive products stay off the storefront', function () {
    $branch = Branch::factory()->create();
    Product::factory()->create(['slug' => 'hidden-pack', 'status' => false]);

    expect(collect(StorefrontCatalog::products($branch))->pluck('id'))->not->toContain('hidden-pack');
});

test('inventory service adjusts quantity and writes a ledger row', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $inventory = app(InventoryService::class)->firstOrCreate($branch, $product);

    $updated = app(InventoryService::class)->adjust(
        $inventory,
        InventoryTransactionType::StockIn,
        10,
    );

    expect($updated->quantity)->toBe(10)
        ->and($updated->transactions()->count())->toBe(1);

    $reduced = app(InventoryService::class)->adjust(
        $updated,
        InventoryTransactionType::StockOut,
        4,
    );

    expect($reduced->quantity)->toBe(6)
        ->and($reduced->getAttributes())->not->toHaveKeys([
            'available_quantity',
            'minimum_stock',
        ]);
});
