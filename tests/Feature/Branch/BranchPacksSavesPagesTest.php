<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Inventory;
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
});

test('guests are redirected to login from packs and saves pages', function (string $routeName) {
    $this->get(route($routeName, $this->branch))->assertRedirect(route('login'));
})->with([
    'branch.packs-saves.index',
    'branch.packs-saves.create',
]);

test('branch staff can view the packs and saves list', function () {
    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.packs-saves.index', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/packs-saves/index')
            ->has('packs', 3)
            ->where('packs.0.name', 'Starter trio'));
});

test('branch staff can view the create pack page with this branch stock products', function () {
    $product = Product::factory()->create([
        'name' => 'Packable Device',
        'selling_price' => 25.00,
        'status' => true,
    ]);

    Inventory::factory()->create([
        'branch_id' => $this->branch->id,
        'product_id' => $product->id,
        'quantity' => 8,
    ]);

    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $otherProduct = Product::factory()->create([
        'name' => 'Other Branch Only',
        'selling_price' => 12.00,
        'status' => true,
    ]);
    Inventory::factory()->create([
        'branch_id' => $otherBranch->id,
        'product_id' => $otherProduct->id,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($this->staff, 'branch')
        ->get(route('branch.packs-saves.create', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/packs-saves/create')
            ->has('availableProducts'));

    $ids = collect($response->inertiaProps('availableProducts'))->pluck('id');

    expect($ids)->toContain($product->id)
        ->and($ids)->not->toContain($otherProduct->id);
});

test('staff without manage-branch-products cannot open packs and saves', function () {
    $user = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'branch')
        ->get(route('branch.packs-saves.index', $this->branch))
        ->assertForbidden();
});
