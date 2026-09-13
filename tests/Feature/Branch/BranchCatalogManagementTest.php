<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('branch can create and update a global category', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.categories.store', $branch), [
            'name' => 'Branch Coils',
            'slug' => 'branch-coils',
            'status' => true,
        ])
        ->assertRedirect(route('branch.categories.show', [$branch, 'branch-coils']));

    $category = Category::query()->where('slug', 'branch-coils')->firstOrFail();

    expect($category->name)->toBe('Branch Coils');

    $this->actingAs($staff, 'branch')
        ->put(route('branch.categories.update', [$branch, $category]), [
            'name' => 'Branch Coils Updated',
            'slug' => 'branch-coils',
            'status' => true,
        ])
        ->assertRedirect(route('branch.categories.show', [$branch, 'branch-coils']));

    expect($category->fresh()->name)->toBe('Branch Coils Updated');
});

test('branch can update a global product without destroy routes', function () {
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Editable Air Pro',
        'slug' => 'editable-air-pro',
    ]);
    $product->categories()->sync([$category->id]);

    $this->actingAs($staff, 'branch')
        ->put(route('branch.products.update', [$branch, $product]), [
            'category_ids' => [$category->id],
            'name' => 'Editable Air Pro Plus',
            'slug' => 'editable-air-pro',
            'sku' => $product->sku,
            'status' => true,
        ])
        ->assertRedirect(route('branch.products.show', [$branch, 'editable-air-pro']));

    expect($product->fresh()->name)->toBe('Editable Air Pro Plus');

    expect(Route::has('branch.categories.destroy'))->toBeFalse()
        ->and(Route::has('branch.products.destroy'))->toBeFalse()
        ->and(Route::has('branch.categories.restore'))->toBeFalse();
});

test('branch catalog mutations require manage-branch-products', function () {
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $user = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'branch')
        ->post(route('branch.categories.store', $branch), [
            'name' => 'Denied Category',
            'slug' => 'denied-category',
            'status' => true,
        ])
        ->assertForbidden();
});
