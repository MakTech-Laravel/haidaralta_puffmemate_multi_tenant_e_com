<?php

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use App\Services\Inventory\BranchProductAvailabilityService;
use App\Services\Inventory\InventoryService;

test('platform product create leaves all branches disabled', function () {
    $category = Category::factory()->create();
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    $product = app(ProductService::class)->createFromPlatform([
        'category_id' => $category->id,
        'name' => 'Classic Pack',
        'slug' => 'classic-pack',
        'sku' => 'SKU-CLASSIC',
        'status' => true,
    ]);

    expect(BranchProduct::query()->where('product_id', $product->id)->count())->toBe(0)
        ->and($product->isEnabledForBranch($branchA->id))->toBeFalse()
        ->and($product->isEnabledForBranch($branchB->id))->toBeFalse();
});

test('branch product create enables only the creating branch', function () {
    $category = Category::factory()->create();
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    $product = app(ProductService::class)->createFromBranch($branchA, [
        'category_id' => $category->id,
        'name' => 'Branch Pack',
        'slug' => 'branch-pack',
        'sku' => 'SKU-BRANCH',
        'status' => true,
    ]);

    expect($product->isEnabledForBranch($branchA->id))->toBeTrue()
        ->and($product->isEnabledForBranch($branchB->id))->toBeFalse()
        ->and(BranchProduct::query()->where('product_id', $product->id)->count())->toBe(1);
});

test('availability service can enable a disabled branch product', function () {
    $product = Product::factory()->create();
    $branch = Branch::factory()->create();
    $availability = app(BranchProductAvailabilityService::class);

    expect($availability->isEnabled($branch, $product))->toBeFalse();

    $availability->setEnabled($branch, $product, true);

    expect($availability->isEnabled($branch, $product))->toBeTrue();
});

test('inventory service adjusts stock and writes a ledger row', function () {
    $branch = Branch::factory()->create();
    $product = Product::factory()->create();
    $inventory = app(InventoryService::class)->firstOrCreate($branch, $product);

    $updated = app(InventoryService::class)->adjust(
        $inventory,
        InventoryTransactionType::StockIn,
        10,
    );

    expect($updated->quantity)->toBe(10)
        ->and($updated->available_quantity)->toBe(10)
        ->and($updated->transactions()->count())->toBeGreaterThanOrEqual(1);
});
