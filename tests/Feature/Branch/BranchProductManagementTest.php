<?php

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
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

test('branch create stores stock and price for the creating branch only', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branchA = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $branchB = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.store', $branchA), [
            'category_ids' => [$category->id],
            'name' => 'Sydney CBD Exclusive',
            'slug' => 'sydney-cbd-exclusive',
            'sku' => 'SKU-SYD-1',
            'status' => true,
            'selling_price' => 19.99,
            'quantity' => 5,
        ])
        ->assertRedirect(route('branch.products.show', [$branchA, 'sydney-cbd-exclusive']));

    $product = Product::query()->where('slug', 'sydney-cbd-exclusive')->firstOrFail();

    $inventory = Inventory::query()
        ->where('branch_id', $branchA->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->quantity)->toBe(5)
        ->and(
            Inventory::query()
                ->where('branch_id', $branchB->id)
                ->where('product_id', $product->id)
                ->exists(),
        )->toBeFalse();

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
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
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

test('a catalog product created elsewhere is listed at this branch', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    Product::factory()->create(['slug' => 'shared-catalog-pack', 'status' => true]);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.index', $branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/products/index')
            ->where('table.data', fn ($rows) => collect($rows)
                ->contains(fn ($row) => $row['slug'] === 'shared-catalog-pack')));
});

test('branch routes no longer expose an enablement toggle', function () {
    expect(Route::has('branch.products.enable'))->toBeFalse();
});

test('branch can reuse a product with initial stock and price', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'reuse-me']);

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.reuse', $branch), [
            'product_id' => $product->id,
            'selling_price' => 11.5,
            'quantity' => 4,
        ])
        ->assertRedirect(route('branch.products.show', [$branch, $product]));

    $inventory = Inventory::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->first();

    expect($inventory)->not->toBeNull()
        ->and($inventory->quantity)->toBe(4);
});

test('branch can adjust inventory and price for a variant', function () {
    config(['catalog.show_product_variants' => true]);

    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 7,
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
            ->component('branch/catalog/products/show')
            ->has('product.variant_inventories.'.$variant->id)
            ->where('product.variant_inventories.'.$variant->id.'.quantity', 7)
            ->has('product.variant_prices.'.$variant->id)
            ->where('product.variant_prices.'.$variant->id.'.selling_price', '15.25'));
});

test('branch product inventory adjustment sets counted on-hand with a reason', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.inventory', [$branch, $product]), [
            'type' => 'adjustment',
            'quantity' => 7,
            'reason' => 'damaged',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('inventories', [
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 7,
    ]);

    $this->assertDatabaseHas('inventory_transactions', [
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => -3,
        'reason' => 'damaged',
    ]);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.inventory.quantity', 7)
            ->where('transactions.data.0.reason_label', 'Damaged')
            ->where('transactions.data.0.quantity', -3)
            ->has('adjustmentReasons'));
});

test('branch product index shows stock and filters by missing price', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $priced = Product::factory()->create(['name' => 'Priced Pack', 'slug' => 'priced-pack']);
    $unpriced = Product::factory()->create(['name' => 'Unpriced Pack', 'slug' => 'unpriced-pack']);

    $inventory = app(InventoryService::class)->firstOrCreate($branch, $priced);
    app(InventoryService::class)->adjust($inventory, InventoryTransactionType::StockIn, 9);

    BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $priced->id,
        'variant_id' => null,
        'selling_price' => 12.5,
        'status' => true,
    ]);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.index', $branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/products/index')
            ->where('table.data', fn ($rows) => collect($rows)
                ->firstWhere('slug', 'priced-pack')['quantity'] === 9));

    $this->actingAs($staff, 'branch')
        ->get(route('branch.products.index', [
            'branch' => $branch,
            'filter' => ['missing_price' => '1'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/products/index')
            ->where('table.data', fn ($rows) => collect($rows)->contains(fn ($row) => $row['slug'] === 'unpriced-pack')
                && ! collect($rows)->contains(fn ($row) => $row['slug'] === 'priced-pack')));
});

test('branch routes do not expose product destroy', function () {
    expect(Route::has('branch.products.destroy'))->toBeFalse();
});
