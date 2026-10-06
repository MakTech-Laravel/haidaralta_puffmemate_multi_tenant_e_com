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
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
});

test('guests are redirected from assign products', function () {
    $this->get(route('branch.assign-products.create', $this->branch))
        ->assertRedirect(route('login'));
});

test('legacy nested assign-products url redirects to sibling create route', function () {
    $this->actingAs($this->staff, 'branch')
        ->get("/branch/{$this->branch->slug}/drivers/assign-products")
        ->assertRedirect(route('branch.assign-products.create', $this->branch));
});

test('staff without manage-branch-drivers cannot open assign products', function () {
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $staff->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.assign-products.create', $this->branch))
        ->assertForbidden();
});

test('branch staff can open assign products with this branch drivers and stock', function () {
    $product = Product::factory()->create(['status' => true, 'name' => 'Assignable Pack']);
    Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 12,
        'reserved_quantity' => 2,
        'status' => true,
    ]);

    $otherProduct = Product::factory()->create(['status' => true, 'name' => 'Other Branch Stock']);
    Inventory::factory()->create([
        'branch_id' => $this->otherBranch->id,
        'product_id' => $otherProduct->id,
        'quantity' => 20,
        'status' => true,
    ]);

    $response = $this->actingAs($this->staff, 'branch')
        ->get(route('branch.assign-products.create', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/drivers/assign-products')
            ->has('driverOptionsUrl')
            ->has('products')
            ->where('products.0.id', $product->id)
            ->where('products.0.available', 10));

    $ids = collect($response->inertiaProps('products'))->pluck('id');

    expect($ids)->toContain($product->id)
        ->and($ids)->not->toContain($otherProduct->id);
});

test('driver options are scoped to this branch and searchable', function () {
    $foreignDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'name' => 'Foreign Driver',
        'branch_id' => null,
        'is_active' => true,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $foreignDriver->id,
        'branch_id' => $this->otherBranch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $this->get(route('branch.assign-products.driver-options', $this->branch))
        ->assertRedirect(route('login'));

    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $staff->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.assign-products.driver-options', $this->branch))
        ->assertForbidden();

    $response = $this->actingAs($this->staff, 'branch')
        ->get(route('branch.assign-products.driver-options', [
            'branch' => $this->branch,
            'filter' => ['search' => 'Daniel'],
            'per_page' => 20,
        ]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'sku']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($this->driver->id)
        ->and($ids)->not->toContain($foreignDriver->id);
});

test('single driver assign reduces branch stock and increases driver inventory', function () {
    $product = Product::factory()->create(['status' => true]);
    $inventory = Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.assign-products.store', $this->branch), [
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
        ->assertRedirect(route('branch.assign-products.create', $this->branch));

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
        ->where('type', InventoryTransactionType::DriverAssign)
        ->first();

    expect($driverTxn)->not->toBeNull()
        ->and($driverTxn->quantity)->toBe(3)
        ->and($driverTxn->before_quantity)->toBe(0)
        ->and($driverTxn->after_quantity)->toBe(3)
        ->and($driverTxn->reason)->toBeNull()
        ->and(DriverInventoryTransaction::query()->count())->toBe(1);
});

test('multiple drivers each receive the same quantity and branch loses N times qty', function () {
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
    $inventory = Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->post(route('branch.assign-products.store', $this->branch), [
            'mode' => DriverStockAssignmentMode::Multiple->value,
            'driver_ids' => [$this->driver->id, $secondDriver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        ])
        ->assertRedirect(route('branch.assign-products.create', $this->branch));

    expect($inventory->fresh()->quantity)->toBe(14)
        ->and(DriverInventory::query()
            ->where('user_id', $this->driver->id)
            ->where('product_id', $product->id)
            ->value('quantity'))->toBe(3)
        ->and(DriverInventory::query()
            ->where('user_id', $secondDriver->id)
            ->where('product_id', $product->id)
            ->value('quantity'))->toBe(3)
        ->and(InventoryTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $product->id)
            ->where('type', InventoryTransactionType::DriverAssign)
            ->value('quantity'))->toBe(6);

    $driverTxns = DriverInventoryTransaction::query()
        ->where('branch_id', $this->branch->id)
        ->where('product_id', $product->id)
        ->where('type', InventoryTransactionType::DriverAssign)
        ->orderBy('user_id')
        ->get();

    expect($driverTxns)->toHaveCount(2)
        ->and($driverTxns->pluck('user_id')->all())->toEqualCanonicalizing([
            $this->driver->id,
            $secondDriver->id,
        ])
        ->and($driverTxns->every(fn (DriverInventoryTransaction $txn): bool => $txn->quantity === 3
            && $txn->before_quantity === 0
            && $txn->after_quantity === 3))->toBeTrue();
});

test('insufficient stock is rejected without writing driver inventory', function () {
    $product = Product::factory()->create(['status' => true]);
    $inventory = Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.assign-products.create', $this->branch))
        ->post(route('branch.assign-products.store', $this->branch), [
            'mode' => DriverStockAssignmentMode::Single->value,
            'driver_ids' => [$this->driver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 6,
                ],
            ],
        ])
        ->assertRedirect(route('branch.assign-products.create', $this->branch))
        ->assertSessionHasErrors('items');

    expect($inventory->fresh()->quantity)->toBe(5)
        ->and(DriverInventory::query()->count())->toBe(0)
        ->and(DriverInventoryTransaction::query()->count())->toBe(0);
});

test('driver from another branch cannot be assigned stock', function () {
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
        'status' => true,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->from(route('branch.assign-products.create', $this->branch))
        ->post(route('branch.assign-products.store', $this->branch), [
            'mode' => DriverStockAssignmentMode::Single->value,
            'driver_ids' => [$foreignDriver->id],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])
        ->assertRedirect(route('branch.assign-products.create', $this->branch))
        ->assertSessionHasErrors('driver_ids');

    expect(DriverInventory::query()->count())->toBe(0);
});
