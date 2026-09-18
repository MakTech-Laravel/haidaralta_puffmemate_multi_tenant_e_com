<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductManageRedirect;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->product = Product::factory()->create(['slug' => 'branch-stock-pack']);
});

test('manage stock page with a product renders inventory payload without transactions', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/manage')
            ->where('context.locked', 'branch')
            ->where('product.id', $this->product->id)
            ->has('transactionsUrl')
            ->missing('transactions'));
});

test('manage stock page with a locked pair keeps the product locked', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(ProductManageRedirect::branchUrl($this->branch, $this->product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/manage')
            ->where('context.locked', 'both')
            ->where('context.product.id', $this->product->id)
            ->where('product.id', $this->product->id)
            ->missing('transactions'));
});

test('manage stock page with no product shows pickers only', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/manage')
            ->where('context.locked', 'branch')
            ->where('context.branch.id', $this->branch->id)
            ->where('product', null)
            ->missing('transactions'));
});

test('update price url with a product redirects to manage stock', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.price', [
            'branch' => $this->branch,
            'product' => $this->product->slug,
        ]))
        ->assertRedirect(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]));
});

test('product show renders catalog details without a branch network table', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.products.show', [$this->branch, $this->product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/products/show')
            ->where('product.id', $this->product->id)
            ->where('product.name', $this->product->name)
            ->missing('table')
            ->missing('inventoryUrl')
            ->missing('transactions'));
});

test('manage stock url 404s when a locked product is missing', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => 'no-such-product',
            'locked' => 'both',
        ]))
        ->assertNotFound();
});

test('branch import stock page is not found while bulk import is hidden', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.import', $this->branch))
        ->assertNotFound();
});

test('branch import stock store is not found while bulk import is hidden', function () {
    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.stock.import.store', $this->branch), [
            'product_ids' => [$this->product->id],
            'quantity' => 6,
        ])
        ->assertNotFound();
});

test('branch import stock page locks the branch even when unlocked in the query', function () {
    config(['catalog.show_bulk_import' => true]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.import', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/import')
            ->where('context.locked', 'branch')
            ->where('context.branch.id', $this->branch->id));
});

test('branch import stock only fills this branch', function () {
    config(['catalog.show_bulk_import' => true]);

    $other = Product::factory()->create();

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.stock.import.store', $this->branch), [
            'product_ids' => [$this->product->id, $other->id],
            'quantity' => 6,
        ])
        ->assertRedirect();

    expect(Inventory::query()->where('quantity', 6)->pluck('branch_id')->unique()->all())
        ->toBe([$this->branch->id]);
});

test('branch stock transactions only list this branch ledger', function () {
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $melbourneStaff = User::query()->where('email', UserSeeder::BRANCH_2_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.products.inventory', [$this->branch, $this->product]), [
            'type' => 'stock_in',
            'buying_price' => 10,
            'quantity' => 4,
        ])
        ->assertRedirect();

    $this->actingAs($melbourneStaff, 'branch')
        ->post(route('branch.products.inventory', [$melbourne, $this->product]), [
            'type' => 'stock_in',
            'buying_price' => 10,
            'quantity' => 9,
        ])
        ->assertRedirect();

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.stock-transactions', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/reports/stock-transactions')
            ->where('table.meta.total', 1)
            ->where('table.data.0.quantity', 4)
            ->where('table.data.0.product_url', route('branch.products.show', [$this->branch, $this->product]))
            ->where('table.data.0.created_by_url', route('branch.staff.show', [$this->branch, $this->staff]))
            ->missing('table.filterOptions.products')
            ->where('table.filterOptions.selected_branch', null));

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.stock-transactions', [
            $this->branch,
            'filter' => ['search' => $this->branch->name],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 0));
});

test('branch stock product options paginate catalog products', function () {
    Product::factory()->count(21)->create();

    $this->actingAs($this->staff, 'branch')
        ->getJson(route('branch.stock.product-options', [
            'branch' => $this->branch,
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('meta.total', 22);
});

test('creating a product from a branch stock page returns to it with the product selected', function () {
    $returnTo = '/branch/'.$this->branch->slug.'/stock/import';

    $response = $this->actingAs($this->staff, 'branch')
        ->post(route('branch.products.store', $this->branch), [
            'category_ids' => [Category::factory()->create()->id],
            'name' => 'Branch Returned Pack',
            'slug' => 'branch-returned-pack',
            'status' => true,
            'return_to' => $returnTo,
        ]);

    $created = Product::query()->where('slug', 'branch-returned-pack')->firstOrFail();

    $response->assertRedirect($returnTo.'?product='.$created->id);
});

test('guests cannot open branch stock pages', function () {
    config(['catalog.show_bulk_import' => true]);

    $this->get(route('branch.stock.import', $this->branch))->assertRedirect(route('login'));
});
