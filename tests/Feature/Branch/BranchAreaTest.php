<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\User;
use App\Support\Geo\Polygon;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
});

/**
 * @return array<string, mixed>
 */
function branchAreaPayload(array $overrides = []): array
{
    return [
        'name' => 'Surry Hills',
        'address' => '100 Crown St, Surry Hills NSW 2010',
        'latitude' => -33.8845,
        'longitude' => 151.2140,
        'polygon' => Polygon::squareAround(-33.8845, 151.2140),
        'city' => 'Sydney',
        'state' => 'NSW',
        'postal_code' => '2010',
        'notes' => 'Inner city zone',
        'is_active' => true,
        ...$overrides,
    ];
}

test('branch admin can list create update show and delete areas', function () {
    $this->actingAs($this->admin, 'branch')
        ->get(route('branch.areas.index', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/areas/index')
            ->where('branch.slug', $this->branch->slug)
            ->has('table.data'));

    $this->actingAs($this->admin, 'branch')
        ->post(route('branch.areas.store', $this->branch), branchAreaPayload())
        ->assertRedirect();

    $area = BranchArea::query()
        ->where('name', 'Surry Hills')
        ->whereHas('branches', fn ($query) => $query->where('branches.id', $this->branch->id))
        ->firstOrFail();

    expect($area->name)->toBe('Surry Hills')
        ->and((float) $area->latitude)->toBe(-33.8845)
        ->and($area->is_active)->toBeTrue()
        ->and($area->polygon)->toBeArray()
        ->and(Polygon::uniqueVertexCount($area->polygon))->toBeGreaterThanOrEqual(3);

    $this->actingAs($this->admin, 'branch')
        ->get(route('branch.areas.show', [$this->branch, $area]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/areas/show')
            ->where('area.name', 'Surry Hills')
            ->has('area.polygon')
            ->has('area.map.google')
            ->has('area.map.open_street_map')
            ->has('area.map.embed'));

    $this->actingAs($this->admin, 'branch')
        ->put(route('branch.areas.update', [$this->branch, $area]), branchAreaPayload([
            'name' => 'Darlinghurst',
            'is_active' => false,
        ]))
        ->assertRedirect(route('branch.areas.show', [$this->branch, $area]));

    expect($area->fresh()->name)->toBe('Darlinghurst')
        ->and($area->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin, 'branch')
        ->delete(route('branch.areas.destroy', [$this->branch, $area]))
        ->assertRedirect(route('branch.areas.index', $this->branch));

    expect(BranchArea::query()->whereKey($area->id)->exists())->toBeFalse();
});

test('branch staff without manage-branch-settings cannot access areas', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.areas.index', $this->branch))
        ->assertForbidden();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.areas.store', $this->branch), branchAreaPayload())
        ->assertForbidden();
});

test('branch cannot mutate an area that belongs to another branch', function () {
    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $area = BranchArea::factory()->forBranch($otherBranch)->create();

    $this->actingAs($this->admin, 'branch')
        ->get(route('branch.areas.show', [$this->branch, $area]))
        ->assertNotFound();

    $this->actingAs($this->admin, 'branch')
        ->put(route('branch.areas.update', [$this->branch, $area]), branchAreaPayload())
        ->assertNotFound();

    $this->actingAs($this->admin, 'branch')
        ->delete(route('branch.areas.destroy', [$this->branch, $area]))
        ->assertNotFound();
});

test('branch manager with settings permission can open areas index', function () {
    $manager = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $manager->assignRole(RoleEnum::MANAGER->value);

    $this->actingAs($manager, 'branch')
        ->get(route('branch.areas.index', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('branch/areas/index'));
});

test('branch rejects duplicate area names globally', function () {
    BranchArea::factory()->forBranch($this->branch)->create([
        'name' => 'Surry Hills',
    ]);

    $this->actingAs($this->admin, 'branch')
        ->from(route('branch.areas.create', $this->branch))
        ->post(route('branch.areas.store', $this->branch), branchAreaPayload([
            'name' => 'SURRY HILLS',
        ]))
        ->assertRedirect(route('branch.areas.create', $this->branch))
        ->assertSessionHasErrors(['name']);
});
