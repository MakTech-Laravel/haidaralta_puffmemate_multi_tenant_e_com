<?php

use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $this->sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
});

/**
 * @return array<string, mixed>
 */
function branchFormAreaCreatePayload(array $overrides = []): array
{
    return [
        'name' => 'Area Assign Store',
        'slug' => 'area-assign-store',
        'code' => 'AAS-001',
        'phone' => '0298765432',
        'email' => 'area-assign@test.com',
        'address' => '100 Assign St',
        'opening_time' => '09:00',
        'closing_time' => '21:00',
        'status' => true,
        'super_admin' => [
            'name' => 'Area Assign Admin',
            'phone' => '0412000088',
            'email' => 'area.assign.admin@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ],
        ...$overrides,
    ];
}

test('platform create and edit forms expose active area options', function () {
    $active = BranchArea::factory()->forBranch($this->sydney)->create([
        'name' => 'Active Option Area',
        'is_active' => true,
    ]);
    BranchArea::factory()->forBranch($this->sydney)->inactive()->create([
        'name' => 'Inactive Option Area',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/create')
            ->has('areaOptions')
            ->where('areaOptions', fn ($options) => collect($options)->contains(
                fn ($row) => $row['id'] === $active->id
                    && $row['name'] === 'Active Option Area'
                    && in_array($this->sydney->name, $row['branch_names'], true),
            ))
            ->where('areaOptions', fn ($options) => collect($options)->every(
                fn ($row) => $row['name'] !== 'Inactive Option Area',
            ))
        );

    $owned = BranchArea::factory()->forBranch($this->melbourne)->create([
        'name' => 'Melbourne Owned',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.edit', $this->melbourne))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/edit')
            ->where('branch.area_ids', [$owned->id])
            ->has('areaOptions')
        );
});

test('creating a branch with area_ids attaches those areas without removing other memberships', function () {
    $area = BranchArea::factory()->forBranch($this->sydney)->create([
        'name' => 'Form Attach Area',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branches.store'), branchFormAreaCreatePayload([
            'area_ids' => [$area->id],
        ]))
        ->assertRedirect(route('platform.branches.show', 'area-assign-store'));

    $branch = Branch::query()->where('slug', 'area-assign-store')->firstOrFail();
    $area->refresh();

    expect($area->branches()->whereKey($branch->id)->exists())->toBeTrue()
        ->and($area->branches()->whereKey($this->sydney->id)->exists())->toBeTrue()
        ->and($area->branches()->count())->toBe(2);
});

test('editing a branch attaches selected areas from another branch without stealing', function () {
    $target = Branch::factory()->create([
        'name' => 'Target Store',
        'slug' => 'target-store',
        'code' => 'TGT-001',
        'status' => true,
    ]);
    (new RolesAndPermissionsSeeder)->seedBranchRolesForBranch($target);

    $area = BranchArea::factory()->forBranch($this->sydney)->create([
        'name' => 'Edit Attach Area',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.branches.update', $target), [
            'name' => $target->name,
            'slug' => $target->slug,
            'code' => $target->code,
            'status' => true,
            'area_ids' => [$area->id],
        ])
        ->assertRedirect(route('platform.branches.show', $target));

    expect($area->fresh()->branches()->whereKey($target->id)->exists())->toBeTrue()
        ->and($area->fresh()->branches()->whereKey($this->sydney->id)->exists())->toBeTrue();
});

test('deselected areas currently on the branch stay attached on update', function () {
    $branch = Branch::factory()->create([
        'slug' => 'keep-areas',
        'code' => 'KEP-001',
        'status' => true,
    ]);
    (new RolesAndPermissionsSeeder)->seedBranchRolesForBranch($branch);

    $keep = BranchArea::factory()->forBranch($branch)->create([
        'name' => 'Keep Me',
    ]);
    $other = BranchArea::factory()->forBranch($this->melbourne)->create([
        'name' => 'Bring Me',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.branches.update', $branch), [
            'name' => $branch->name,
            'slug' => $branch->slug,
            'code' => $branch->code,
            'status' => true,
            'area_ids' => [$other->id],
        ])
        ->assertRedirect(route('platform.branches.show', $branch));

    expect($keep->fresh()->branches()->whereKey($branch->id)->exists())->toBeTrue()
        ->and($other->fresh()->branches()->whereKey($branch->id)->exists())->toBeTrue()
        ->and($branch->areas()->pluck('branch_areas.id')->sort()->values()->all())
        ->toBe(collect([$keep->id, $other->id])->sort()->values()->all());
});

test('unknown area_ids are rejected on branch create', function () {
    $before = Branch::query()->count();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branches.store'), branchFormAreaCreatePayload([
            'area_ids' => [999_999],
        ]))
        ->assertSessionHasErrors(['area_ids.0']);

    expect(Branch::query()->count())->toBe($before);
});
