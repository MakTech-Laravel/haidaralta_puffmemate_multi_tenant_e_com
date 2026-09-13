<?php

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Inventory\BranchProductAvailabilityService;
use App\Services\Inventory\InventoryService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('branch create enables only the creating branch', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branchA = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $branchB = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.store', $branchA), [
            'category_ids' => [$category->id],
            'name' => 'Downtown Exclusive',
            'slug' => 'downtown-exclusive',
            'sku' => 'SKU-DT-1',
            'status' => true,
            'selling_price' => 19.99,
            'quantity' => 5,
            'minimum_stock' => 2,
        ])
        ->assertRedirect(route('branch.products.show', [$branchA, 'downtown-exclusive']));

    $product = Product::query()->where('slug', 'downtown-exclusive')->firstOrFail();

    expect($product->isEnabledForBranch($branchA->id))->toBeTrue()
        ->and($product->isEnabledForBranch($branchB->id))->toBeFalse()
        ->and(BranchProduct::query()->where('product_id', $product->id)->count())->toBe(1);

    $inventory = Inventory::query()
        ->where('branch_id', $branchA->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->quantity)->toBe(5)
        ->and($inventory->minimum_stock)->toBe(2);

    expect(
        BranchProductPrice::query()
            ->where('branch_id', $branchA->id)
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->exists(),
    )->toBeTrue();
});

test('branch create persists gallery images', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.store', $branch), [
            'category_ids' => [$category->id],
            'name' => 'Gallery Branch Pack',
            'slug' => 'gallery-branch-pack',
            'status' => true,
            'image' => UploadedFile::fake()->image('primary.jpg', 400, 400),
            'gallery' => [
                UploadedFile::fake()->image('gallery-a.jpg', 400, 400),
                UploadedFile::fake()->image('gallery-b.jpg', 400, 400),
            ],
        ])
        ->assertRedirect(route('branch.products.show', [$branch, 'gallery-branch-pack']));

    $product = Product::query()->where('slug', 'gallery-branch-pack')->firstOrFail();

    expect($product->image)->not->toBeNull()
        ->and($product->images()->count())->toBe(2);
});

test('disabling a product at branch A does not affect branch B', function () {
    $staffA = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branchA = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $branchB = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $product = Product::factory()->create();
    $availability = app(BranchProductAvailabilityService::class);

    $availability->setEnabled($branchA, $product, true);
    $availability->setEnabled($branchB, $product, true);

    $this->actingAs($staffA, 'branch')
        ->put(route('branch.products.enable', [$branchA, $product]), [
            'status' => false,
        ])
        ->assertRedirect();

    expect($product->isEnabledForBranch($branchA->id))->toBeFalse()
        ->and($product->isEnabledForBranch($branchB->id))->toBeTrue();
});

test('branch can reuse a product with initial stock and price', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $product = Product::factory()->create(['slug' => 'reuse-me']);

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.reuse', $branch), [
            'product_id' => $product->id,
            'selling_price' => 11.5,
            'quantity' => 4,
            'minimum_stock' => 1,
        ])
        ->assertRedirect(route('branch.products.show', [$branch, $product]));

    expect($product->isEnabledForBranch($branch->id))->toBeTrue();

    $inventory = Inventory::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->quantity)->toBe(4)
        ->and($inventory->minimum_stock)->toBe(1);
});

test('branch can adjust inventory and price for a variant', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    app(BranchProductAvailabilityService::class)->setEnabled($branch, $product, true);

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 7,
            'minimum_stock' => 2,
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($staff, 'branch')
        ->put(route('branch.products.prices', [$branch, $product]), [
            'selling_price' => 15.25,
            'variant_id' => $variant->id,
            'status' => true,
        ])
        ->assertRedirect();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/products/show')
            ->has('product.variant_inventories.'.$variant->id)
            ->where('product.variant_inventories.'.$variant->id.'.quantity', 7)
            ->has('product.variant_prices.'.$variant->id)
            ->where('product.variant_prices.'.$variant->id.'.selling_price', '15.25'));
});

test('branch product index filters by enabled and low stock', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $availability = app(BranchProductAvailabilityService::class);
    $inventory = app(InventoryService::class);

    $enabled = Product::factory()->create(['name' => 'Enabled Filter Pack', 'slug' => 'enabled-filter-pack']);
    $disabled = Product::factory()->create(['name' => 'Disabled Filter Pack', 'slug' => 'disabled-filter-pack']);
    $low = Product::factory()->create(['name' => 'Low Stock Filter Pack', 'slug' => 'low-stock-filter-pack']);

    $availability->setEnabled($branch, $enabled, true);
    $availability->setEnabled($branch, $low, true);

    $row = $inventory->firstOrCreate($branch, $low, null, ['minimum_stock' => 5]);
    $inventory->setMinimumStock($row, 5);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.index', [
            'branch' => $branch,
            'filter' => ['enabled' => '1'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/products/index')
            ->has('table.data')
            ->where('table.data', fn ($rows) => collect($rows)->contains(fn ($row) => $row['slug'] === 'enabled-filter-pack')
                && collect($rows)->contains(fn ($row) => $row['slug'] === 'low-stock-filter-pack')
                && ! collect($rows)->contains(fn ($row) => $row['slug'] === 'disabled-filter-pack')));

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.index', [
            'branch' => $branch,
            'filter' => ['low_stock' => '1'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/products/index')
            ->where('table.data', fn ($rows) => collect($rows)->contains(fn ($row) => $row['slug'] === 'low-stock-filter-pack')
                && ! collect($rows)->contains(fn ($row) => $row['slug'] === 'enabled-filter-pack')));
});

test('branch routes do not expose product destroy', function () {
    expect(Route::has('branch.products.destroy'))->toBeFalse();
});
