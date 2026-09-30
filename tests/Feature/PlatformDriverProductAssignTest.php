<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStockAssignmentMode;
use App\Enums\InventoryTransactionType;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverInventory;
use App\Models\DriverInventoryTransaction;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
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
    $this->otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
});

test('guests are redirected from platform assign products', function () {
    $this->get(route('platform.assign-products.create'))
        ->assertRedirect(route('login'));
});

test('platform staff without manage-drivers cannot open assign products', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.assign-products.create'))
        ->assertForbidden();
});

test('platform admin can open assign products empty until branch selected', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.assign-products.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/assign-products')
            ->where('branch', null)
            ->where('driverOptionsUrl', null)
            ->where('products', []));
});

test('platform assign products loads branch stock when branch query is set', function () {
    $product = Product::factory()->create(['status' => true, 'name' => 'Platform Assign Pack']);
    Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 12,
        'reserved_quantity' => 2,
        'status' => true,
    ]);

    $otherProduct = Product::factory()->create(['status' => true, 'name' => 'Other Branch Pack']);
    Inventory::factory()->create([
        'branch_id' => $this->otherBranch->id,
        'product_id' => $otherProduct->id,
        'quantity' => 20,
        'status' => true,
    ]);

    $response = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.assign-products.create', ['branch' => $this->branch->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/assign-products')
            ->where('branch.id', $this->branch->id)
            ->has('driverOptionsUrl')
            ->where('products.0.id', $product->id)
            ->where('products.0.available', 10));

    $ids = collect($response->inertiaProps('products'))->pluck('id');

    expect($ids)->toContain($product->id)
        ->and($ids)->not->toContain($otherProduct->id);
});

test('platform rejects assign when driver is not on the selected branch', function () {
    $foreignDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $foreignDriver->id,
        'branch_id' => $this->otherBranch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $product = Product::factory()->create(['status' => true]);
    Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.assign-products.create', ['branch' => $this->branch->id]))
        ->post(route('platform.assign-products.store'), [
            'branch_id' => $this->branch->id,
            'mode' => DriverStockAssignmentMode::Single->value,
            'driver_ids' => [$foreignDriver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 2,
                ],
            ],
        ])
        ->assertRedirect(route('platform.assign-products.create', ['branch' => $this->branch->id]))
        ->assertSessionHasErrors('driver_ids');

    expect(DriverInventory::query()->count())->toBe(0)
        ->and(DriverInventoryTransaction::query()->count())->toBe(0);
});

test('platform single driver assign writes ledger and reduces branch stock', function () {
    $product = Product::factory()->create(['status' => true]);
    $inventory = Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.assign-products.store'), [
            'branch_id' => $this->branch->id,
            'mode' => DriverStockAssignmentMode::Single->value,
            'driver_ids' => [$this->driver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'quantity' => 3,
                ],
            ],
        ])
        ->assertRedirect(route('platform.assign-products.create', [
            'branch' => $this->branch->id,
        ]));

    expect($inventory->fresh()->quantity)->toBe(7)
        ->and(DriverInventory::query()
            ->where('user_id', $this->driver->id)
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $product->id)
            ->value('quantity'))->toBe(3)
        ->and(InventoryTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $product->id)
            ->where('type', InventoryTransactionType::DriverAssign)
            ->value('quantity'))->toBe(3);

    $driverTxn = DriverInventoryTransaction::query()
        ->where('branch_id', $this->branch->id)
        ->where('user_id', $this->driver->id)
        ->where('product_id', $product->id)
        ->first();

    expect($driverTxn)->not->toBeNull()
        ->and($driverTxn->quantity)->toBe(3)
        ->and($driverTxn->before_quantity)->toBe(0)
        ->and($driverTxn->after_quantity)->toBe(3);
});

test('platform multiple drivers each get a ledger row', function () {
    $secondDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $secondDriver->id,
        'branch_id' => $this->branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $product = Product::factory()->create(['status' => true]);
    Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.assign-products.store'), [
            'branch_id' => $this->branch->id,
            'mode' => DriverStockAssignmentMode::Multiple->value,
            'driver_ids' => [$this->driver->id, $secondDriver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ])
        ->assertRedirect(route('platform.assign-products.create', [
            'branch' => $this->branch->id,
        ]));

    $driverTxns = DriverInventoryTransaction::query()
        ->where('branch_id', $this->branch->id)
        ->where('product_id', $product->id)
        ->where('type', InventoryTransactionType::DriverAssign)
        ->get();

    expect($driverTxns)->toHaveCount(2)
        ->and($driverTxns->pluck('user_id')->all())->toEqualCanonicalizing([
            $this->driver->id,
            $secondDriver->id,
        ])
        ->and($driverTxns->every(fn (DriverInventoryTransaction $txn): bool => $txn->quantity === 3))->toBeTrue();
});
