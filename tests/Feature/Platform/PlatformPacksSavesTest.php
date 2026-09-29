<?php

use App\Enums\PackOfferType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Pack;
use App\Models\Product;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $this->sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
});

function platformStockedProduct(Branch $branch, int $quantity = 10): Product
{
    $product = Product::factory()->create(['status' => true, 'selling_price' => 20.00]);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => $quantity,
        'status' => true,
    ]);

    return $product;
}

function platformPackPayload(Branch $branch, Product $first, Product $second, array $overrides = []): array
{
    return [
        'branch_id' => $branch->id,
        'name' => 'Platform duo',
        'offer_type' => PackOfferType::Percent->value,
        'offer_value' => 10,
        'quantity' => 2,
        'status' => true,
        'items' => [
            ['product_id' => $first->id, 'quantity' => 1],
            ['product_id' => $second->id, 'quantity' => 2],
        ],
        ...$overrides,
    ];
}

function platformBranchStock(Branch $branch, Product $product): ?int
{
    return Inventory::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->whereNull('variant_id')
        ->value('quantity');
}

test('guests are redirected from platform packs and saves', function () {
    $this->get(route('platform.packs-saves.index'))
        ->assertRedirect(route('login'));
});

test('platform staff without manage-catalog cannot open packs and saves', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.packs-saves.index'))
        ->assertForbidden();
});

test('platform index lists every branch pack and filters by branch', function () {
    Pack::factory()->for($this->sydney)->create(['name' => 'Sydney pack']);
    Pack::factory()->for($this->melbourne)->create(['name' => 'Melbourne pack']);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.packs-saves.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/packs-saves/index')
            ->has('table.data', 2));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.packs-saves.index', ['filter' => ['branch_id' => $this->melbourne->id]]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Melbourne pack')
            ->where('table.data.0.branch_name', $this->melbourne->name)
            ->where('table.filterOptions.selected_branch.id', $this->melbourne->id));
});

test('platform create loads the selected branch stock products', function () {
    $product = platformStockedProduct($this->melbourne);
    platformStockedProduct($this->sydney);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.packs-saves.create', ['branch' => $this->melbourne->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/packs-saves/create')
            ->where('selectedBranch.id', $this->melbourne->id)
            ->has('availableProducts', 1)
            ->where('availableProducts.0.id', $product->id));
});

test('platform store creates the pack for the chosen branch and reserves its stock', function () {
    $first = platformStockedProduct($this->melbourne);
    $second = platformStockedProduct($this->melbourne);

    $response = $this->actingAs($this->admin, 'platform')
        ->post(route('platform.packs-saves.store'), platformPackPayload($this->melbourne, $first, $second));

    $pack = Pack::query()->firstOrFail();

    $response->assertRedirect(route('platform.packs-saves.show', $pack));

    expect($pack->branch_id)->toBe($this->melbourne->id)
        ->and($pack->items)->toHaveCount(2)
        ->and($pack->highlighted_at)->toBeNull()
        ->and(platformBranchStock($this->melbourne, $first))->toBe(8)
        ->and(platformBranchStock($this->melbourne, $second))->toBe(6);
});

test('platform store requires a branch', function () {
    $first = platformStockedProduct($this->sydney);
    $second = platformStockedProduct($this->sydney);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.packs-saves.store'), platformPackPayload($this->sydney, $first, $second, [
            'branch_id' => null,
        ]))
        ->assertInvalid(['branch_id' => 'The branch field is required.']);

    expect(Pack::query()->count())->toBe(0);
});

test('platform store rejects a product not stocked at the chosen branch', function () {
    $first = platformStockedProduct($this->melbourne);
    $sydneyOnly = platformStockedProduct($this->sydney);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.packs-saves.store'), platformPackPayload($this->melbourne, $first, $sydneyOnly))
        ->assertInvalid(['items.1.product_id']);

    expect(Pack::query()->count())->toBe(0)
        ->and(platformBranchStock($this->sydney, $sydneyOnly))->toBe(10);
});

test('platform can view edit update and delete any branch pack by id', function () {
    $first = platformStockedProduct($this->melbourne);
    $second = platformStockedProduct($this->melbourne);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.packs-saves.store'), platformPackPayload($this->melbourne, $first, $second));

    $pack = Pack::query()->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.packs-saves.show', $pack))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/packs-saves/show')
            ->where('branch.id', $this->melbourne->id)
            ->where('pack.id', $pack->id));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.packs-saves.edit', $pack))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/packs-saves/edit')
            ->has('availableProducts', 2));

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.packs-saves.update', $pack), [
            'name' => 'Renamed duo',
            'offer_type' => $pack->offer_type->value,
            'offer_value' => $pack->offer_value,
            'quantity' => 3,
            'status' => true,
        ])
        ->assertRedirect(route('platform.packs-saves.show', $pack));

    expect($pack->refresh()->name)->toBe('Renamed duo')
        ->and(platformBranchStock($this->melbourne, $first))->toBe(7);

    $this->actingAs($this->admin, 'platform')
        ->delete(route('platform.packs-saves.destroy', $pack))
        ->assertRedirect(route('platform.packs-saves.index'));

    expect(Pack::query()->count())->toBe(0)
        ->and(platformBranchStock($this->melbourne, $first))->toBe(10);
});

test('an unknown pack id is not found on the platform', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.packs-saves.show', ['pack' => 999]))
        ->assertNotFound();
});

test('highlighting a pack clears the previous highlight across branches', function () {
    $previous = Pack::factory()->for($this->sydney)->create(['highlighted_at' => now()->subDay()]);
    $pack = Pack::factory()->for($this->melbourne)->create();

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.packs-saves.index'))
        ->put(route('platform.packs-saves.highlight', $pack), ['highlighted' => true])
        ->assertRedirect(route('platform.packs-saves.index'));

    expect($pack->refresh()->highlighted_at)->not->toBeNull()
        ->and($previous->refresh()->highlighted_at)->toBeNull();

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.packs-saves.highlight', $pack), ['highlighted' => false]);

    expect($pack->refresh()->highlighted_at)->toBeNull();
});

test('storing a highlighted pack clears the previous highlight', function () {
    $previous = Pack::factory()->for($this->sydney)->create(['highlighted_at' => now()->subDay()]);
    $first = platformStockedProduct($this->melbourne);
    $second = platformStockedProduct($this->melbourne);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.packs-saves.store'), platformPackPayload($this->melbourne, $first, $second, [
            'is_highlighted' => true,
        ]));

    $pack = Pack::query()->where('branch_id', $this->melbourne->id)->firstOrFail();

    expect($pack->highlighted_at)->not->toBeNull()
        ->and($previous->refresh()->highlighted_at)->toBeNull();
});
