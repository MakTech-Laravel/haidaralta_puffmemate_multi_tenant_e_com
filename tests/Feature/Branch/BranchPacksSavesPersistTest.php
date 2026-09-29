<?php

use App\Enums\InventoryTransactionType;
use App\Enums\PackOfferType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Pack;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
});

function stockedProduct(Branch $branch, int $quantity = 10, array $attributes = []): Product
{
    $product = Product::factory()->create([
        'status' => true,
        'selling_price' => 20.00,
        ...$attributes,
    ]);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => $quantity,
        'status' => true,
    ]);

    return $product;
}

function packPayload(Product $first, Product $second, int $quantity = 2, array $overrides = []): array
{
    return [
        'name' => 'Starter trio',
        'description' => 'A save for this branch.',
        'offer_type' => PackOfferType::Percent->value,
        'offer_value' => 20,
        'quantity' => $quantity,
        'status' => true,
        'items' => [
            ['product_id' => $first->id, 'quantity' => 1],
            ['product_id' => $second->id, 'quantity' => 2],
        ],
        ...$overrides,
    ];
}

test('guests are redirected to login when storing a pack', function () {
    $this->post(route('branch.packs-saves.store', $this->branch), [])
        ->assertRedirect(route('login'));
});

test('staff without manage-branch-products cannot store a pack', function () {
    $user = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $user->assignRole(RoleEnum::STAFF->value);

    $first = stockedProduct($this->branch);
    $second = stockedProduct($this->branch);

    $this->actingAs($user, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second))
        ->assertForbidden();

    expect(Pack::query()->count())->toBe(0);
});

test('staff store persists pack items and media and reserves stock', function () {
    Storage::fake('public');

    $first = stockedProduct($this->branch, 10, ['name' => 'Device One', 'sku' => 'SKU-A']);
    $second = stockedProduct($this->branch, 10, ['name' => 'Device Two', 'sku' => 'SKU-B']);

    $response = $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), [
            ...packPayload($first, $second, 2),
            'image' => UploadedFile::fake()->image('cover.jpg'),
            'gallery' => [UploadedFile::fake()->image('gallery.jpg')],
        ]);

    $pack = Pack::query()->where('branch_id', $this->branch->id)->firstOrFail();

    $response->assertRedirect(route('branch.packs-saves.show', [$this->branch, $pack]));

    expect($pack->name)->toBe('Starter trio')
        ->and($pack->status)->toBeTrue()
        ->and($pack->quantity)->toBe(2)
        ->and($pack->items)->toHaveCount(2)
        ->and($pack->images)->toHaveCount(1)
        ->and($pack->image)->not->toBeNull();

    expect(
        Inventory::query()
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $first->id)
            ->whereNull('variant_id')
            ->value('quantity'),
    )->toBe(8)
        ->and(
            Inventory::query()
                ->where('branch_id', $this->branch->id)
                ->where('product_id', $second->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(6);

    expect(
        InventoryTransaction::query()
            ->where('reference_type', Pack::class)
            ->where('reference_id', $pack->id)
            ->where('type', InventoryTransactionType::Reserve)
            ->count(),
    )->toBe(2);
});

test('store is rejected when a line lacks stock', function () {
    $first = stockedProduct($this->branch, 1);
    $second = stockedProduct($this->branch, 10);

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.packs-saves.create', $this->branch))
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 2))
        ->assertRedirect(route('branch.packs-saves.create', $this->branch))
        ->assertInvalid(['items.0.quantity'])
        ->assertValid(['quantity', 'items.1.quantity']);

    expect(Pack::query()->count())->toBe(0)
        ->and(
            Inventory::query()
                ->where('product_id', $first->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(1);
});

test('store is rejected for a product without this branch inventory', function () {
    $first = stockedProduct($this->branch);
    $other = Product::factory()->create(['status' => true, 'selling_price' => 12]);

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.packs-saves.create', $this->branch))
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $other))
        ->assertInvalid(['items.1.product_id']);

    expect(Pack::query()->count())->toBe(0);
});

test('store is rejected for a product stocked only at another branch', function () {
    $first = stockedProduct($this->branch);
    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $other = stockedProduct($otherBranch, 8, ['name' => 'Other Branch Only']);

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.packs-saves.create', $this->branch))
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $other))
        ->assertInvalid(['items.1.product_id']);

    expect(Pack::query()->count())->toBe(0);
});

test('increasing pack quantity reserves more stock and decreasing releases it', function () {
    $first = stockedProduct($this->branch, 20);
    $second = stockedProduct($this->branch, 20);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 2));

    $pack = Pack::query()->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.packs-saves.update', [$this->branch, $pack]), [
            'name' => $pack->name,
            'offer_type' => $pack->offer_type->value,
            'offer_value' => $pack->offer_value,
            'quantity' => 4,
            'status' => true,
        ])
        ->assertRedirect(route('branch.packs-saves.show', [$this->branch, $pack]));

    expect(
        Inventory::query()
            ->where('product_id', $first->id)
            ->whereNull('variant_id')
            ->value('quantity'),
    )->toBe(16)
        ->and(
            Inventory::query()
                ->where('product_id', $second->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(12);

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.packs-saves.update', [$this->branch, $pack]), [
            'name' => $pack->name,
            'offer_type' => $pack->offer_type->value,
            'offer_value' => $pack->offer_value,
            'quantity' => 3,
            'status' => true,
        ]);

    expect(
        Inventory::query()
            ->where('product_id', $first->id)
            ->whereNull('variant_id')
            ->value('quantity'),
    )->toBe(17)
        ->and(
            InventoryTransaction::query()
                ->where('reference_id', $pack->id)
                ->where('type', InventoryTransactionType::Release)
                ->exists(),
        )->toBeTrue();
});

test('increasing pack quantity beyond a line stock reports the error on that line', function () {
    $first = stockedProduct($this->branch, 20);
    $second = stockedProduct($this->branch, 5);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 2));

    $pack = Pack::query()->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.packs-saves.edit', [$this->branch, $pack]))
        ->put(route('branch.packs-saves.update', [$this->branch, $pack]), [
            'name' => $pack->name,
            'offer_type' => $pack->offer_type->value,
            'offer_value' => $pack->offer_value,
            'quantity' => 4,
            'status' => true,
        ])
        ->assertInvalid(['items.1.quantity'])
        ->assertValid(['quantity', 'items.0.quantity']);

    expect($pack->refresh()->quantity)->toBe(2)
        ->and(
            Inventory::query()
                ->where('product_id', $second->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(1);
});

test('a pack that reserved the last units stays active while other packs needing that sku turn off', function () {
    $shared = stockedProduct($this->branch, 5, ['name' => 'Shared Device']);
    $firstMate = stockedProduct($this->branch, 5, ['name' => 'Mate One']);
    $secondMate = stockedProduct($this->branch, 5, ['name' => 'Mate Two']);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), [
            ...packPayload($shared, $firstMate, 4, [
                'name' => 'First pack',
                'items' => [
                    ['product_id' => $shared->id, 'quantity' => 1],
                    ['product_id' => $firstMate->id, 'quantity' => 1],
                ],
            ]),
        ]);

    $firstPack = Pack::query()->where('name', 'First pack')->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), [
            ...packPayload($shared, $secondMate, 1, [
                'name' => 'Last units pack',
                'items' => [
                    ['product_id' => $shared->id, 'quantity' => 1],
                    ['product_id' => $secondMate->id, 'quantity' => 1],
                ],
            ]),
        ]);

    $secondPack = Pack::query()->where('name', 'Last units pack')->firstOrFail();

    expect($secondPack->fresh()->status)->toBeTrue()
        ->and($firstPack->fresh()->status)->toBeFalse()
        ->and(
            Inventory::query()
                ->where('product_id', $shared->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(0);
});

test('a later stock out below a pack line qty deactivates that pack without auto-on after restock', function () {
    $first = stockedProduct($this->branch, 5);
    $second = stockedProduct($this->branch, 5);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 1, [
            'items' => [
                ['product_id' => $first->id, 'quantity' => 2],
                ['product_id' => $second->id, 'quantity' => 1],
            ],
        ]));

    $pack = Pack::query()->firstOrFail();
    expect($pack->status)->toBeTrue();

    $inventory = Inventory::query()
        ->where('branch_id', $this->branch->id)
        ->where('product_id', $first->id)
        ->whereNull('variant_id')
        ->firstOrFail();

    app(InventoryService::class)->adjust(
        $inventory,
        InventoryTransactionType::StockOut,
        2,
        $this->staff,
    );

    expect($pack->fresh()->status)->toBeFalse()
        ->and($inventory->fresh()->quantity)->toBe(1);

    app(InventoryService::class)->adjust(
        $inventory->fresh(),
        InventoryTransactionType::StockIn,
        10,
        $this->staff,
    );

    expect($pack->fresh()->status)->toBeFalse();
});

test('deleting a pack releases remaining allocation', function () {
    $first = stockedProduct($this->branch, 10);
    $second = stockedProduct($this->branch, 10);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 2));

    $pack = Pack::query()->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->delete(route('branch.packs-saves.destroy', [$this->branch, $pack]))
        ->assertRedirect(route('branch.packs-saves.index', $this->branch));

    expect(Pack::query()->count())->toBe(0)
        ->and(
            Inventory::query()
                ->where('product_id', $first->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(10)
        ->and(
            Inventory::query()
                ->where('product_id', $second->id)
                ->whereNull('variant_id')
                ->value('quantity'),
        )->toBe(10)
        ->and(
            InventoryTransaction::query()
                ->where('type', InventoryTransactionType::Release)
                ->exists(),
        )->toBeTrue();
});

test('branch staff cannot highlight a pack', function () {
    $first = stockedProduct($this->branch);
    $second = stockedProduct($this->branch);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 1, [
            'is_highlighted' => true,
        ]));

    $pack = Pack::query()->firstOrFail();

    $this->actingAs($this->staff, 'branch')
        ->put(route('branch.packs-saves.update', [$this->branch, $pack]), [
            'name' => $pack->name,
            'offer_type' => $pack->offer_type->value,
            'offer_value' => $pack->offer_value,
            'quantity' => $pack->quantity,
            'status' => true,
            'is_highlighted' => true,
        ])
        ->assertRedirect(route('branch.packs-saves.show', [$this->branch, $pack]));

    expect($pack->refresh()->highlighted_at)->toBeNull();
});

test('a pack from another branch is not found on this branch url', function () {
    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $pack = Pack::factory()->create([
        'branch_id' => $otherBranch->id,
        'name' => 'Melbourne pack',
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.packs-saves.show', [$this->branch, $pack->slug]))
        ->assertNotFound();
});

test('turning a pack active fails when remaining stock is below a line qty', function () {
    $first = stockedProduct($this->branch, 5);
    $second = stockedProduct($this->branch, 5);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.packs-saves.store', $this->branch), packPayload($first, $second, 1, [
            'status' => false,
            'items' => [
                ['product_id' => $first->id, 'quantity' => 2],
                ['product_id' => $second->id, 'quantity' => 1],
            ],
        ]));

    $pack = Pack::query()->firstOrFail();

    $inventory = Inventory::query()
        ->where('product_id', $first->id)
        ->whereNull('variant_id')
        ->firstOrFail();

    app(InventoryService::class)->adjust(
        $inventory,
        InventoryTransactionType::StockOut,
        2,
        $this->staff,
    );

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.packs-saves.edit', [$this->branch, $pack]))
        ->put(route('branch.packs-saves.update', [$this->branch, $pack]), [
            'name' => $pack->name,
            'offer_type' => $pack->offer_type->value,
            'offer_value' => $pack->offer_value,
            'quantity' => $pack->quantity,
            'status' => true,
        ])
        ->assertRedirect(route('branch.packs-saves.edit', [$this->branch, $pack]))
        ->assertInvalid(['status']);

    expect($pack->fresh()->status)->toBeFalse();
});
