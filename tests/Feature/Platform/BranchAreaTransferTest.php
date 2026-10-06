<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\BranchAreaTransferHistory;
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

    $this->fromBranch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->toBranch = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();
    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
});

/**
 * @return array<string, mixed>
 */
function areaTransferPayload(Branch $to, array $overrides = []): array
{
    return [
        'to_branch_id' => $to->id,
        'effective_date' => now()->toDateString(),
        'reason' => 'Jamuna became its own branch.',
        ...$overrides,
    ];
}

test('platform admin can transfer an area between branches and history is recorded', function () {
    $area = BranchArea::factory()->create([
        'branch_id' => $this->fromBranch->id,
        'name' => 'Jamuna',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertRedirect(route('platform.branch-areas.show', $area));

    $area->refresh();

    expect($area->branch_id)->toBe($this->toBranch->id);

    $history = BranchAreaTransferHistory::query()
        ->where('branch_area_id', $area->id)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_branch_id)->toBe($this->fromBranch->id)
        ->and($history->to_branch_id)->toBe($this->toBranch->id)
        ->and($history->reason)->toBe('Jamuna became its own branch.')
        ->and($history->transferred_by)->toBe($this->admin->id);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.show', $area))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-areas/show')
            ->where('branch.id', $this->toBranch->id)
            ->has('area.transfers', 1)
            ->where('area.transfers.0.from_branch_id', $this->fromBranch->id)
            ->where('area.transfers.0.to_branch_id', $this->toBranch->id));

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branches.areas.show', [$this->fromBranch, $area]))
        ->assertNotFound();
});

test('platform cannot transfer an area to the same branch', function () {
    $area = BranchArea::factory()->create([
        'branch_id' => $this->fromBranch->id,
        'name' => 'Kuril',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branch-areas.show', $area))
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->fromBranch))
        ->assertRedirect(route('platform.branch-areas.show', $area))
        ->assertSessionHasErrors(['to_branch_id']);

    expect($area->fresh()->branch_id)->toBe($this->fromBranch->id)
        ->and(BranchAreaTransferHistory::query()->where('branch_area_id', $area->id)->exists())->toBeFalse();
});

test('platform cannot transfer when destination already has the same area name', function () {
    BranchArea::factory()->create([
        'branch_id' => $this->toBranch->id,
        'name' => 'Jamuna',
    ]);

    $area = BranchArea::factory()->create([
        'branch_id' => $this->fromBranch->id,
        'name' => 'Jamuna',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branch-areas.show', $area))
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertRedirect(route('platform.branch-areas.show', $area))
        ->assertSessionHasErrors(['to_branch_id']);

    expect($area->fresh()->branch_id)->toBe($this->fromBranch->id);
});

test('platform user without edit-branches cannot transfer areas', function () {
    $viewer = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $viewer->assignRole(RoleEnum::STAFF->value);
    $viewer->givePermissionTo(PermissionEnum::VIEW_BRANCHES->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $area = BranchArea::factory()->create([
        'branch_id' => $this->fromBranch->id,
    ]);

    $this->actingAs($viewer, 'platform')
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertForbidden();
});

test('branch portal cannot transfer areas', function () {
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $area = BranchArea::factory()->create([
        'branch_id' => $this->fromBranch->id,
    ]);

    $this->actingAs($branchAdmin, 'branch')
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertRedirect();

    expect($area->fresh()->branch_id)->toBe($this->fromBranch->id);
});
