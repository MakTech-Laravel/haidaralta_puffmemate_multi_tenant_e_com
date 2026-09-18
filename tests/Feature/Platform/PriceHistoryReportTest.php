<?php

use App\Enums\PriceChangeSource;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchProductPriceHistory;
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
    $this->product = Product::factory()->create(['slug' => 'price-history-pack', 'sku' => 'PH-SKU']);
});

test('price history report lists rows and keeps product and branch pickers out of the payload catalogs', function () {
    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'created_by' => $this->admin->id,
        'source' => PriceChangeSource::PriceUpsert,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/reports/price-history')
            ->where('table.meta.total', 1)
            ->missing('table.filterOptions.products')
            ->missing('table.filterOptions.branches')
            ->has('table.filterOptions.sources')
            ->has('table.data.0.product_url')
            ->has('table.data.0.product_sku')
            ->has('table.data.0.branch_url')
            ->has('table.data.0.branch_name')
            ->has('table.data.0.changes')
            ->where('table.data.0.created_by_url', route('platform.admins.show', $this->admin)));
});

test('price history report filters by the selected product', function () {
    $other = Product::factory()->create(['sku' => 'OTHER-PH']);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'created_by' => $this->admin->id,
    ]);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $other->id,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history', ['product' => $this->product->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 1)
            ->where('table.data.0.product_name', $this->product->name)
            ->where('table.data.0.product_url', route('platform.products.show', $this->product))
            ->where('table.data.0.branch_url', route('platform.branches.show', $this->branch))
            ->where('table.filterOptions.selected_product.id', $this->product->id));
});

test('price history report lists product and branch combinations and filters by branch', function () {
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $other = Product::factory()->create(['sku' => 'COMBO-PH']);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'created_by' => $this->admin->id,
    ]);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $melbourne->id,
        'product_id' => $other->id,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/reports/price-history')
            ->where('table.meta.total', 2)
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['product_name'] === $this->product->name
                    && $row['product_sku'] === $this->product->sku
                    && $row['branch_name'] === $this->branch->name,
            ) && collect($rows)->contains(
                fn ($row) => $row['product_name'] === $other->name
                    && $row['product_sku'] === 'COMBO-PH'
                    && $row['branch_name'] === $melbourne->name,
            )));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history', [
            'branch' => $melbourne->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 1)
            ->where('table.data.0.product_name', $other->name)
            ->where('table.data.0.product_sku', 'COMBO-PH')
            ->where('table.data.0.branch_name', $melbourne->name)
            ->where('table.filterOptions.selected_branch.id', $melbourne->id));
});

test('price history report filters by source and search', function () {
    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'source' => PriceChangeSource::PriceUpsert,
        'created_by' => $this->admin->id,
    ]);

    $stockInProduct = Product::factory()->create(['sku' => 'PH-IN', 'name' => 'Unique Price History Pack']);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $stockInProduct->id,
        'source' => PriceChangeSource::StockIn,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history', [
            'filter' => ['source' => PriceChangeSource::StockIn->value],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 1)
            ->where('table.data.0.source', PriceChangeSource::StockIn->value)
            ->where('table.data.0.source_label', 'New entry'));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history', [
            'filter' => ['search' => 'Unique Price History Pack'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 1)
            ->where('table.data.0.product_name', 'Unique Price History Pack'));
});

test('price history report paginates past the default page size', function () {
    BranchProductPriceHistory::factory()
        ->count(16)
        ->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'created_by' => $this->admin->id,
        ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.meta.total', 16)
            ->where('table.meta.per_page', 15)
            ->where('table.meta.last_page', 2)
            ->has('table.data', 15));
});

test('price history report returns 404 when the product query does not resolve', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history', ['product' => 999999]))
        ->assertNotFound();
});

test('staff without inventory permission cannot open price history', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.reports.price-history'))
        ->assertForbidden();
});

test('guests cannot open the price history report', function () {
    $this->get(route('platform.reports.price-history'))->assertRedirect(route('login'));
});

test('guests cannot export price history', function () {
    $this->get(route('platform.reports.price-history.export', [
        'format' => 'csv',
        'scope' => 'all',
    ]))->assertRedirect(route('login'));
});

test('staff without inventory permission cannot export price history', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.reports.price-history.export', [
            'format' => 'csv',
            'scope' => 'all',
        ]))
        ->assertForbidden();
});

test('price history csv export all ignores product filters and excel is a spreadsheet', function () {
    $this->travelTo('2026-09-18 12:00:00');

    $other = Product::factory()->create(['sku' => 'PH-EXPORT-OTHER']);

    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $this->product->id,
        'created_by' => $this->admin->id,
    ]);
    BranchProductPriceHistory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $other->id,
        'created_by' => $this->admin->id,
    ]);

    $all = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history.export', [
            'format' => 'csv',
            'scope' => 'all',
            'filter' => ['product_id' => $this->product->id],
        ]));

    $all->assertOk()->assertDownload('price-history-all-2026-09-18.csv');
    $allContents = file_get_contents($all->baseResponse->getFile()->getPathname());

    expect($allContents)
        ->toContain('Product')
        ->toContain($this->product->name)
        ->toContain('PH-EXPORT-OTHER');

    $filtered = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history.export', [
            'format' => 'csv',
            'scope' => 'filters',
            'filter' => ['product_id' => $this->product->id],
        ]));

    $filtered->assertOk();

    expect(file_get_contents($filtered->baseResponse->getFile()->getPathname()))
        ->toContain($this->product->name)
        ->not->toContain('PH-EXPORT-OTHER');

    $page = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.price-history.export', [
            'format' => 'xlsx',
            'scope' => 'page',
            'per_page' => 15,
            'page' => 1,
        ]));

    $page->assertOk()->assertDownload('price-history-page-2026-09-18.xlsx');

    expect(substr(file_get_contents($page->baseResponse->getFile()->getPathname()), 0, 2))->toBe('PK');

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.reports.price-history'))
        ->get(route('platform.reports.price-history.export', [
            'format' => 'csv',
            'scope' => 'period',
        ]))
        ->assertRedirect(route('platform.reports.price-history'))
        ->assertSessionHasErrors('filter.from');
});
