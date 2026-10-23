<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchArea;
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

    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
});

/**
 * @return array<string, mixed>
 */
function platformAreaPayload(array $overrides = []): array
{
    return [
        'name' => 'Surry Hills',
        'address' => '100 Crown St, Surry Hills NSW 2010',
        'latitude' => -33.8845,
        'longitude' => 151.2140,
        'city' => 'Sydney',
        'state' => 'NSW',
        'postal_code' => '2010',
        'notes' => 'Inner city zone',
        'is_active' => true,
        ...$overrides,
    ];
}

test('platform admin can open the global areas list', function () {
    BranchArea::factory()->forBranch($this->branch)->create([
        'name' => 'Surry Hills',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-areas/index')
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Surry Hills')
            ->where('table.data.0.branch.slug', $this->branch->slug)
            ->where('canManage', true));
});

test('platform admin can open global create and store an area with branch_id', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('platform/branch-areas/create'));

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branch-areas.store'), platformAreaPayload([
            'branch_id' => $this->branch->id,
            'name' => 'Global Create Zone',
        ]))
        ->assertRedirect();

    $area = BranchArea::query()
        ->where('name', 'Global Create Zone')
        ->whereHas('branches', fn ($query) => $query->where('branches.id', $this->branch->id))
        ->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branch-areas.store'), platformAreaPayload([
            'name' => 'Missing Branch Zone',
        ]))
        ->assertSessionHasErrors(['branch_id']);

    expect($area->city)->toBe('Sydney');
});

test('nested platform areas index redirects to the global list with branch filter', function () {
    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.areas.index', $this->branch))
        ->assertRedirect(route('platform.branch-areas.index', [
            'filter' => ['branch_id' => $this->branch->id],
        ]));
});

test('global areas list can filter by branch', function () {
    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    BranchArea::factory()->forBranch($this->branch)->create([
        'name' => 'Sydney Zone',
    ]);
    BranchArea::factory()->forBranch($otherBranch)->create([
        'name' => 'Melbourne Zone',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.index', [
            'filter' => ['branch_id' => $this->branch->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-areas/index')
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Sydney Zone')
            ->where('table.filterOptions.selected_branch.id', $this->branch->id));
});

test('platform admin can create update show and delete branch areas on global routes', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branch-areas.store'), platformAreaPayload([
            'branch_id' => $this->branch->id,
        ]))
        ->assertRedirect();

    $area = BranchArea::query()
        ->where('name', 'Surry Hills')
        ->whereHas('branches', fn ($query) => $query->where('branches.id', $this->branch->id))
        ->firstOrFail();

    expect($area->name)->toBe('Surry Hills')
        ->and($area->city)->toBe('Sydney')
        ->and($area->state)->toBe('NSW')
        ->and($area->is_active)->toBeTrue();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.show', $area))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-areas/show')
            ->where('area.name', 'Surry Hills')
            ->where('branch.slug', $this->branch->slug)
            ->has('area.map.google')
            ->has('area.map.open_street_map')
            ->has('area.map.embed'));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.edit', $area))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-areas/edit')
            ->where('area.name', 'Surry Hills'));

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.branch-areas.update', $area), platformAreaPayload([
            'name' => 'Paddington',
            'is_active' => false,
            'postal_code' => null,
            'notes' => null,
        ]))
        ->assertRedirect(route('platform.branch-areas.show', $area));

    $area->refresh();

    expect($area->name)->toBe('Paddington')
        ->and($area->is_active)->toBeFalse()
        ->and($area->postal_code)->toBeNull()
        ->and($area->notes)->toBeNull();

    $this->actingAs($this->admin, 'platform')
        ->delete(route('platform.branch-areas.destroy', $area))
        ->assertRedirect(route('platform.branch-areas.index', [
            'filter' => ['branch_id' => $this->branch->id],
        ]));

    expect(BranchArea::query()->whereKey($area->id)->exists())->toBeFalse();
});

test('nested platform area show and edit redirect to global routes', function () {
    $area = BranchArea::factory()->forBranch($this->branch)->create();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.areas.show', [$this->branch, $area]))
        ->assertRedirect(route('platform.branch-areas.show', $area));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.areas.edit', [$this->branch, $area]))
        ->assertRedirect(route('platform.branch-areas.edit', $area));
});

test('platform area store validates required fields', function () {
    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branches.areas.create', $this->branch))
        ->post(route('platform.branches.areas.store', $this->branch), [])
        ->assertRedirect(route('platform.branches.areas.create', $this->branch))
        ->assertSessionHasErrors(['name', 'address', 'latitude', 'longitude', 'city', 'state']);
});

test('platform user without edit-branches cannot create areas', function () {
    $viewer = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $viewer->assignRole(RoleEnum::STAFF->value);
    $viewer->givePermissionTo(PermissionEnum::VIEW_BRANCHES->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($viewer, 'platform')
        ->get(route('platform.branch-areas.index'))
        ->assertOk();

    $this->actingAs($viewer, 'platform')
        ->post(route('platform.branches.areas.store', $this->branch), platformAreaPayload())
        ->assertForbidden();
});

test('platform cannot mutate an area that belongs to another branch via nested routes', function () {
    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $area = BranchArea::factory()->forBranch($otherBranch)->create();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.areas.show', [$this->branch, $area]))
        ->assertNotFound();

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.branches.areas.update', [$this->branch, $area]), platformAreaPayload())
        ->assertNotFound();

    $this->actingAs($this->admin, 'platform')
        ->delete(route('platform.branches.areas.destroy', [$this->branch, $area]))
        ->assertNotFound();
});

test('platform rejects duplicate area names globally', function () {
    BranchArea::factory()->forBranch($this->branch)->create([
        'name' => 'Surry Hills',
    ]);

    $otherBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branch-areas.create'))
        ->post(route('platform.branch-areas.store'), platformAreaPayload([
            'branch_id' => $this->branch->id,
            'name' => 'surry hills',
        ]))
        ->assertRedirect(route('platform.branch-areas.create'))
        ->assertSessionHasErrors(['name']);

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branch-areas.create'))
        ->post(route('platform.branch-areas.store'), platformAreaPayload([
            'branch_id' => $otherBranch->id,
            'name' => 'Surry Hills',
        ]))
        ->assertRedirect(route('platform.branch-areas.create'))
        ->assertSessionHasErrors(['name']);
});

test('platform area update rejects renaming onto another area name', function () {
    BranchArea::factory()->forBranch($this->branch)->create([
        'name' => 'Paddington',
    ]);

    $area = BranchArea::factory()->forBranch($this->branch)->create([
        'name' => 'Potts Point',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branch-areas.edit', $area))
        ->put(route('platform.branch-areas.update', $area), platformAreaPayload([
            'name' => 'Paddington',
        ]))
        ->assertRedirect(route('platform.branch-areas.edit', $area))
        ->assertSessionHasErrors(['name']);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.branch-areas.update', $area), platformAreaPayload([
            'name' => 'Potts Point',
            'notes' => 'Same name is fine',
        ]))
        ->assertRedirect(route('platform.branch-areas.show', $area));

    expect($area->fresh()->notes)->toBe('Same name is fine');
});
