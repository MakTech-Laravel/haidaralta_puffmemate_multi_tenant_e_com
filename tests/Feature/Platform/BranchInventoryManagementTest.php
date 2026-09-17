<?php

use App\Enums\DiscountType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\ProductService;
use App\Support\PlatformTeamResolver;
use App\Support\ProductManageRedirect;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('a platform-created product appears on every branch product list', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    app(ProductService::class)->createFromPlatform([
        'category_ids' => [Category::factory()->create()->id],
        'name' => 'Platform Pack',
        'slug' => 'platform-pack',
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.index', $branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/products/index')
            ->where('table.data', fn ($rows) => collect($rows)
                ->contains(fn ($row) => $row['slug'] === 'platform-pack')));

    expect(Route::has('platform.branches.products.enable'))->toBeFalse();
});

test('platform can set stock and price for any branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 12,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 29.5,
            'discount_type' => 'fixed',
            'discount_price' => 24.0,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('product.inventory.quantity', 12)
            ->where('product.price.selling_price', '29.50')
            ->where('product.price.discount_type', 'fixed')
            ->where('product.price.discount_price', '24.00')
            ->has('product.variant_inventories')
            ->has('product.variant_prices')
            ->has('adjustmentReasons')
            ->has('transactionsUrl')
            ->missing('transactions'));
});

test('platform product inventory adjustment sets counted on-hand with a reason', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
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

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.inventory.quantity', 7)
            ->has('adjustmentReasons')
            ->missing('transactions'));
});

test('platform can set percent discount and clear discount with none', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 40,
            'discount_type' => 'percent',
            'discount_price' => 15,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.price.discount_type', 'percent')
            ->where('product.price.discount_price', '15.00')
            ->where('product.price.start_date', '2026-09-01'));

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 40,
            'discount_type' => 'none',
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.price.discount_type', 'none')
            ->where('product.price.discount_price', null)
            ->where('product.price.start_date', null)
            ->where('product.price.end_date', null));
});

test('manage page hydrates a price without an offer as none', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    BranchProductPrice::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'selling_price' => 20,
        'discount_type' => DiscountType::None,
        'discount_price' => null,
        'start_date' => null,
        'end_date' => null,
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.price.selling_price', '20.00')
            ->where('product.price.discount_type', 'none')
            ->where('product.price.discount_price', null)
            ->where('product.price.start_date', null)
            ->where('product.price.end_date', null));
});

test('platform reduce subtracts a positive quantity', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_out',
            'quantity' => 3,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('inventories', [
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 7,
    ]);
});

test('platform inventory adjustment requires a reason', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'adjustment',
            'quantity' => 4,
        ])
        ->assertSessionHasErrors('reason');
});

test('platform inventory adjustment other stores the typed reason', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'adjustment',
            'quantity' => 8,
            'reason' => 'other',
            'reason_note' => 'Returned from display',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('inventory_transactions', [
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => -2,
        'reason' => 'other',
        'reason_note' => 'Returned from display',
    ]);
});

test('platform inventory adjustment rejects a counted quantity that matches current stock', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 6,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'adjustment',
            'quantity' => 6,
            'reason' => 'count_correction',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Counted quantity matches current stock. No adjustment needed.',
        ]);

    $this->assertDatabaseHas('inventories', [
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 6,
    ]);

    $this->assertDatabaseMissing('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'adjustment',
    ]);
});

test('platform inventory new entry rejects a reason', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 4,
            'reason' => 'damaged',
        ])
        ->assertSessionHasErrors('reason');
});

test('platform inventory writes reject a negative quantity', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_out',
            'quantity' => -5,
        ])
        ->assertSessionHasErrors('quantity');
});

test('platform branch product show includes listing details payload', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create([
        'brand' => 'Geekvape',
        'tags' => ['best_selling'],
        'specifications' => [
            ['label' => 'Resistance', 'value' => '0.2Ω'],
        ],
    ]);

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('product.brand', 'Geekvape')
            ->where('product.tags', ['best_selling'])
            ->where('product.specifications.0.label', 'Resistance')
            ->where('product.specifications.0.value', '0.2Ω')
            ->has('product.created_at')
            ->has('product.updated_at')
            ->has('product.created_by_name')
            ->missing('transactions'));
});

test('platform price upsert without status still stores a live price', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 22.5,
        ])
        ->assertRedirect();

    $price = BranchProductPrice::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->first();

    expect($price)->not->toBeNull()
        ->and($price->status)->toBeTrue();
});

test('platform can set variant stock and price on manage stock', function () {
    config(['catalog.show_product_variants' => true]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 3,
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 9.99,
            'variant_id' => $variant->id,
            'status' => true,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($branch, $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('product.variant_inventories.'.$variant->id.'.quantity', 3)
            ->where('product.variant_prices.'.$variant->id.'.selling_price', '9.99')
            ->missing('transactions'));
});

test('platform can list filter and paginate branch products', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $category = Category::factory()->create(['name' => 'Filter Cat']);
    $matched = Product::factory()->create([
        'name' => 'Alpha Branch Filter Pack',
        'slug' => 'alpha-branch-filter-pack',
        'sku' => 'ABFP-1',
        'status' => true,
    ]);
    $matched->categories()->sync([$category->id]);
    $other = Product::factory()->create([
        'name' => 'Zulu Other Pack',
        'slug' => 'zulu-other-pack',
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $matched]), [
            'type' => 'stock_in',
            'quantity' => 2,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.index', $branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/products/index')
            ->where('summary.products', fn ($value) => $value >= 2)
            ->where('summary.missing_price', fn ($value) => $value >= 1)
            ->where('table.data', fn ($rows) => collect($rows)
                ->firstWhere('slug', 'alpha-branch-filter-pack')['quantity'] === 2)
            ->has('table.filterOptions.categories')
            ->has('table.meta'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.index', [
            'branch' => $branch,
            'filter' => ['search' => 'Alpha Branch'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === $matched->slug,
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.index', [
            'branch' => $branch,
            'filter' => ['category_id' => (string) $category->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === $matched->slug,
            ))
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn ($row) => $row['slug'] !== $other->slug,
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.index', [
            'branch' => $branch,
            'filter' => ['missing_price' => '1'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === $matched->slug && $row['selling_price'] === null,
            )));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.index', [
            'branch' => $branch,
            'per_page' => 15,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.per_page', 15)
            ->where('table.meta.current_page', 1));
});

test('platform can open product branch inventory overview', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'branch-overview-pack']);
    $branchCount = Branch::query()->count();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.branches', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/products/branches')
            ->where('product.slug', 'branch-overview-pack')
            ->where('summary.branches', $branchCount)
            ->has('table.data', $branchCount)
            ->has('table.meta')
            ->has('table.links')
            ->where('table.data.0.manage_url', fn ($url) => is_string($url)
                && str_contains($url, '/platform/stock/manage')
                && str_contains($url, 'locked=both')
                && str_contains($url, 'product='.$product->id)));
});

test('platform can search and filter product branch inventory table', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'branch-filter-pack']);
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $uptown = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$downtown, $product]), [
            'type' => 'stock_in',
            'quantity' => 2,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.branches', [
            'product' => $product,
            'filter' => ['search' => 'Sydney'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.slug', BranchSeeder::SYDNEY_CBD));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.branches', [
            'product' => $product,
            'filter' => ['missing_price' => '1'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === $uptown->slug && $row['selling_price'] === null,
            )));
});

test('platform staff without manage-branch-inventories cannot open product branch overview', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $product = Product::factory()->create();

    $this->actingAs($user, 'platform')
        ->get(route('platform.products.branches', $product))
        ->assertForbidden();
});

test('branch user still cannot soft-delete catalog products after platform inventory ops', function () {
    $product = Product::factory()->create(['slug' => 'still-protected']);
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->delete(route('platform.products.destroy', $product))
        ->assertRedirect(route('login'));

    expect(Product::query()->where('slug', 'still-protected')->exists())->toBeTrue();
});
