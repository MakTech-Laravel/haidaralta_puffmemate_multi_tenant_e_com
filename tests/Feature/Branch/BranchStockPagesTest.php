<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
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

test('manage stock url with a product redirects to this branch product manage page', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => $this->product->id,
        ]))
        ->assertRedirect(route('branch.products.show', [$this->branch, $this->product]));
});

test('manage stock url with no product redirects to this branch products index', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', $this->branch))
        ->assertRedirect(route('branch.products.index', $this->branch));
});

test('update price url with a product redirects to this branch product manage page', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.price', [
            'branch' => $this->branch,
            'product' => $this->product->slug,
        ]))
        ->assertRedirect(route('branch.products.show', [$this->branch, $this->product]));
});

test('manage stock url 404s when the product is missing', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.manage', [
            'branch' => $this->branch,
            'product' => 'no-such-product',
        ]))
        ->assertNotFound();
});

test('branch import stock page locks the branch even when unlocked in the query', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.import', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/import')
            ->where('context.locked', 'branch')
            ->where('context.branch.id', $this->branch->id));
});

test('branch import stock only fills this branch', function () {
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
            'quantity' => 4,
        ])
        ->assertRedirect();

    $this->actingAs($melbourneStaff, 'branch')
        ->post(route('branch.products.inventory', [$melbourne, $this->product]), [
            'type' => 'stock_in',
            'quantity' => 9,
        ])
        ->assertRedirect();

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.stock.transactions', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/stock/transactions')
            ->where('table.meta.total', 1)
            ->where('table.data.0.quantity', 4)
            ->where('table.data.0.product_url', route('branch.products.show', [$this->branch, $this->product]))
            ->where('table.data.0.created_by_url', route('branch.staff.show', [$this->branch, $this->staff]))
            ->missing('table.filterOptions.products'));
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
    $this->get(route('branch.stock.import', $this->branch))->assertRedirect(route('login'));
});
