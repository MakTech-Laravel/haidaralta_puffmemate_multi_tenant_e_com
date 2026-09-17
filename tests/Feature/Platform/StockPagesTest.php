<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use App\Support\ProductManageRedirect;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->product = Product::factory()->create(['slug' => 'stock-pack']);
});

test('manage stock page starts unlocked with no selection', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.manage'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('context.locked', null)
            ->where('context.branch', null)
            ->where('context.product', null)
            ->where('product', null)
            ->missing('transactions'));
});

test('manage stock page with a pair renders listing details without transactions', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('context.locked', null)
            ->where('product.id', $this->product->id)
            ->where('product.name', $this->product->name)
            ->has('transactionsUrl')
            ->missing('transactions'));
});

test('manage stock page with a locked pair keeps both pickers locked', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(ProductManageRedirect::platformUrl($this->branch, $this->product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('context.locked', 'both')
            ->where('context.branch.id', $this->branch->id)
            ->where('context.product.id', $this->product->id)
            ->where('product.id', $this->product->id)
            ->missing('transactions'));
});

test('manage stock page with only a product stays on manage until a branch is picked', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.manage', [
            'product' => $this->product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/manage')
            ->where('context.product.id', $this->product->id)
            ->where('product', null)
            ->missing('transactions'));
});

test('update price url with a pair redirects to manage stock', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.price', [
            'branch' => $this->branch->slug,
            'product' => $this->product->slug,
        ]))
        ->assertRedirect(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]));
});

test('old branch product show url redirects to locked manage stock', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.products.show', [$this->branch, $this->product]))
        ->assertRedirect(ProductManageRedirect::platformUrl($this->branch, $this->product));
});

test('manage stock url 404s when a locked product is missing', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => 'no-such-product',
            'locked' => 'both',
        ]))
        ->assertNotFound();
});

test('import stock page starts unlocked with no selection', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.import'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/import')
            ->where('context.locked', null)
            ->where('context.branch', null)
            ->where('context.product', null));
});

test('import stock adds the same quantity to every branch and product pair', function () {
    $other = Product::factory()->create();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.import.store'), [
            'branch_ids' => [$this->branch->id, $melbourne->id],
            'product_ids' => [$this->product->id, $other->id],
            'quantity' => 3,
        ])
        ->assertRedirect();

    expect(Inventory::query()->where('quantity', 3)->count())->toBe(4);
    expect(InventoryTransaction::query()->count())->toBe(4);
    $this->assertDatabaseHas('inventory_transactions', [
        'type' => 'stock_in',
        'quantity' => 3,
        'reason' => null,
    ]);
});

test('import stock rejects an empty product list', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.import.store'), [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [],
            'quantity' => 3,
        ])
        ->assertSessionHasErrors('product_ids');
});

test('import stock rejects a zero quantity', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.import.store'), [
            'branch_ids' => [$this->branch->id],
            'product_ids' => [$this->product->id],
            'quantity' => 0,
        ])
        ->assertSessionHasErrors('quantity');
});

test('stock transactions ledger filters by the selected product', function () {
    $other = Product::factory()->create();

    foreach ([$this->product, $other] as $product) {
        $this->actingAs($this->admin, 'platform')
            ->post(route('platform.branches.products.inventory', [$this->branch, $product]), [
                'type' => 'stock_in',
                'quantity' => 2,
            ])
            ->assertRedirect();
    }

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.transactions'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/transactions')
            ->where('table.meta.total', 2)
            ->missing('table.filterOptions.products')
            ->missing('table.filterOptions.branches')
            ->has('table.filterOptions.types')
            ->has('table.data.0.product_url')
            ->has('table.data.0.branch_url')
            ->where('table.data.0.created_by_url', route('platform.staff.show', $this->admin)));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.transactions', ['product' => $this->product->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 1)
            ->where('table.data.0.product_name', $this->product->name)
            ->where('table.data.0.product_url', route('platform.products.show', $this->product))
            ->where('table.data.0.branch_url', route('platform.branches.show', $this->branch))
            ->where('table.filterOptions.selected_product.id', $this->product->id));
});

test('stock transactions include the adjustment reason label and signed variance', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$this->branch, $this->product]), [
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$this->branch, $this->product]), [
            'type' => 'adjustment',
            'quantity' => 7,
            'reason' => 'damaged',
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.transactions', ['product' => $this->product->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['type_label'] === 'Adjustment'
                    && $row['reason_label'] === 'Damaged'
                    && $row['quantity'] === -3,
            )));
});

test('stock product options paginate and search instead of returning the full catalog', function () {
    Product::factory()->count(21)->create();

    $this->actingAs($this->admin, 'platform')
        ->getJson(route('platform.stock.product-options', ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('meta.total', 22);

    $this->actingAs($this->admin, 'platform')
        ->getJson(route('platform.stock.product-options', [
            'filter' => ['search' => $this->product->name],
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertJsonFragment(['id' => $this->product->id, 'name' => $this->product->name]);
});

test('stock branch options paginate and search', function () {
    $this->actingAs($this->admin, 'platform')
        ->getJson(route('platform.stock.branch-options', [
            'filter' => ['search' => 'Sydney'],
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonFragment(['slug' => BranchSeeder::SYDNEY_CBD]);
});

test('creating a product from a stock page returns to it with the product selected', function () {
    $category = Category::factory()->create();
    $returnTo = '/platform/stock/import?branch='.$this->branch->id;

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.products.create', ['return_to' => $returnTo]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('returnTo', $returnTo));

    $response = $this->actingAs($this->admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Returned Pack',
            'slug' => 'returned-pack',
            'status' => true,
            'return_to' => $returnTo,
        ]);

    $created = Product::query()->where('slug', 'returned-pack')->firstOrFail();

    $response->assertRedirect(
        '/platform/stock/import?branch='.$this->branch->id.'&product='.$created->id,
    );
});

test('stock pages require manage-branch-inventories', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.stock.import'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->getJson(route('platform.stock.product-options'))
        ->assertForbidden();
});

test('guests cannot open stock pages', function () {
    $this->get(route('platform.stock.import'))->assertRedirect(route('login'));
    $this->getJson(route('platform.stock.product-options'))->assertRedirect(route('login'));
    $this->getJson(route('platform.stock.branch-options'))->assertRedirect(route('login'));
});
