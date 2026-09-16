<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Support\PlatformTeamResolver;
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
            ->has('context.branch_options')
            ->has('context.product_options')
            ->has('adjustmentReasons'));
});

test('manage stock page locks the pair coming from a list row action', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.manage', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
            'locked' => 'both',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('context.locked', 'both')
            ->where('context.branch.id', $this->branch->id)
            ->where('context.product.id', $this->product->id)
            ->where('context.inventory.quantity', 0));
});

test('manage stock page 404s when the locked product is missing', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.manage', [
            'product' => 'no-such-product',
            'locked' => 'product',
        ]))
        ->assertNotFound();
});

test('platform can adjust stock for a branch and product pair', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 8,
        ])
        ->assertRedirect();

    expect(Inventory::query()
        ->where('branch_id', $this->branch->id)
        ->where('product_id', $this->product->id)
        ->whereNull('variant_id')
        ->value('quantity'))->toBe(8);
});

test('platform reduce subtracts a positive quantity', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_out',
            'quantity' => 3,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('inventories', [
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'variant_id' => null,
        'quantity' => 7,
    ]);

    $this->assertDatabaseHas('inventory_transactions', [
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'type' => 'stock_out',
        'quantity' => 3,
        'reason' => null,
    ]);
});

test('platform adjustment sets counted on-hand and stores a signed variance', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 7,
            'reason' => 'damaged',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('inventories', [
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'variant_id' => null,
        'quantity' => 7,
    ]);

    $this->assertDatabaseHas('inventory_transactions', [
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'type' => 'adjustment',
        'quantity' => -3,
        'reason' => 'damaged',
        'reason_note' => null,
        'before_quantity' => 10,
        'after_quantity' => 7,
    ]);
});

test('platform adjustment requires a reason', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 4,
        ])
        ->assertSessionHasErrors('reason');
});

test('platform adjustment other requires a custom reason', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 4,
            'reason' => 'other',
        ])
        ->assertSessionHasErrors('reason_note');
});

test('platform adjustment other stores the typed reason', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 8,
            'reason' => 'other',
            'reason_note' => 'Returned from display',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('inventory_transactions', [
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'type' => 'adjustment',
        'quantity' => -2,
        'reason' => 'other',
        'reason_note' => 'Returned from display',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.transactions', ['product' => $this->product->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['reason_label'] === 'Returned from display',
            )));
});

test('platform named adjustment reasons reject a custom note', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 4,
            'reason' => 'damaged',
            'reason_note' => 'should not be stored',
        ])
        ->assertSessionHasErrors('reason_note');
});

test('platform adjustment rejects a counted quantity that matches current stock', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 6,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
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
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'variant_id' => null,
        'quantity' => 6,
    ]);

    $this->assertDatabaseMissing('inventory_transactions', [
        'product_id' => $this->product->id,
        'type' => 'adjustment',
    ]);
});

test('platform new entry rejects a reason', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 4,
            'reason' => 'damaged',
        ])
        ->assertSessionHasErrors('reason');
});

test('platform stock writes reject a negative quantity', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_out',
            'quantity' => -5,
        ])
        ->assertSessionHasErrors('quantity');
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
});

test('platform can set a selling price and then a discount', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.stock.price.update'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'selling_price' => 20,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.stock.discount.update'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'selling_price' => 20,
            'discount_type' => 'percent',
            'discount_price' => 25,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.discount', [
            'branch' => $this->branch->id,
            'product' => $this->product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/discount')
            ->where('context.price.selling_price', '20.00')
            ->where('context.price.effective_price', '15.00')
            ->where('context.price.has_discount', true));
});

test('stock transactions ledger filters by the selected product', function () {
    $other = Product::factory()->create();

    foreach ([$this->product, $other] as $product) {
        $this->actingAs($this->admin, 'platform')
            ->post(route('platform.stock.adjust'), [
                'branch_id' => $this->branch->id,
                'product_id' => $product->id,
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
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 10,
        ])
        ->assertRedirect();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.stock.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
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
    $returnTo = '/platform/stock/manage?branch='.$this->branch->id;

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
        '/platform/stock/manage?branch='.$this->branch->id.'&product='.$created->id,
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
        ->get(route('platform.stock.manage'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->getJson(route('platform.stock.product-options'))
        ->assertForbidden();
});

test('guests cannot open stock pages', function () {
    $this->get(route('platform.stock.manage'))->assertRedirect(route('login'));
    $this->getJson(route('platform.stock.product-options'))->assertRedirect(route('login'));
    $this->getJson(route('platform.stock.branch-options'))->assertRedirect(route('login'));
});

test('price page shows the current branch price for the selected pair', function () {
    BranchProductPrice::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'variant_id' => null,
        'selling_price' => 14.25,
        'status' => true,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.stock.price', [
            'branch' => $this->branch->slug,
            'product' => $this->product->slug,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/stock/price')
            ->where('context.price.selling_price', '14.25'));
});
