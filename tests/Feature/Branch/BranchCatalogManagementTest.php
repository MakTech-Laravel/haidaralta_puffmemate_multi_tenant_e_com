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

test('branch can update a global category', function () {
    config(['catalog.branch_can_view_categories' => true]);

    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $category = Category::factory()->create([
        'name' => 'Branch Coils',
        'slug' => 'branch-coils',
        'featured' => true,
    ]);

    $this->actingAs($staff, 'branch')
        ->put(route('branch.categories.update', [$branch, $category]), [
            'name' => 'Branch Coils Updated',
            'slug' => 'branch-coils',
            'status' => true,
            'featured' => false,
        ])
        ->assertRedirect(route('branch.categories.show', [$branch, 'branch-coils']));

    expect($category->fresh()->name)->toBe('Branch Coils Updated')
        ->and($category->fresh()->featured)->toBeFalse();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.categories.show', [$branch, $category]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/catalog/categories/show')
            ->where('category.featured', false));
});

test('branch category create is not found while branch create is disabled', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.categories.create', $branch))
        ->assertNotFound();
});

test('branch category store is not found while branch create is disabled', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $before = Category::query()->count();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.categories.store', $branch), [
            'name' => 'Branch Coils',
            'slug' => 'branch-coils',
            'status' => true,
            'featured' => true,
        ])
        ->assertNotFound();

    expect(Category::query()->count())->toBe($before);
});

test('branch can update a global product without destroy routes', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
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
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
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

test('branch category suggestions match existing catalog items', function () {
    config(['catalog.branch_can_view_categories' => true]);

    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $match = Category::factory()->create([
        'name' => 'Pro Coils',
        'slug' => 'pro-coils',
    ]);

    $this->actingAs($staff, 'branch')
        ->getJson(route('branch.categories.suggestions', [$branch, 'q' => 'Pro']))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id]);
});

test('precognitive branch category store is not found while branch create is disabled', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $existing = Category::factory()->create(['slug' => 'branch-taken-slug']);
    $before = Category::query()->count();

    $this->actingAs($staff, 'branch')
        ->withHeaders([
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'slug',
            'Accept' => 'application/json',
        ])
        ->post(route('branch.categories.store', $branch), [
            'slug' => $existing->slug,
        ])
        ->assertNotFound();

    expect(Category::query()->count())->toBe($before);
});

test('precognitive branch product store returns 422 for a taken slug', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $existing = Product::factory()->create(['slug' => 'branch-taken-product']);
    $before = Product::query()->count();

    $this->actingAs($staff, 'branch')
        ->withHeaders([
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'slug',
            'Accept' => 'application/json',
        ])
        ->post(route('branch.products.store', $branch), [
            'slug' => $existing->slug,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug'])
        ->assertHeader('Precognition', 'true');

    expect(Product::query()->count())->toBe($before);
});
