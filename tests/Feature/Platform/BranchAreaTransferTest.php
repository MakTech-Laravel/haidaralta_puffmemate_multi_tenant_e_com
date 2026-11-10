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
        'reason' => 'Also serve this zone from the other store.',
        ...$overrides,
    ];
}

test('platform admin can add an area to another branch without removing existing links', function () {
    $area = BranchArea::factory()->forBranch($this->fromBranch)->create([
        'name' => 'Jamuna',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertRedirect(route('platform.branch-areas.show', $area));

    $area->refresh();

    expect($area->branches()->whereKey($this->fromBranch->id)->exists())->toBeTrue()
        ->and($area->branches()->whereKey($this->toBranch->id)->exists())->toBeTrue();

    $history = BranchAreaTransferHistory::query()
        ->where('branch_area_id', $area->id)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_branch_id)->toBe($this->fromBranch->id)
        ->and($history->to_branch_id)->toBe($this->toBranch->id)
        ->and($history->reason)->toBe('Also serve this zone from the other store.')
        ->and($history->transferred_by)->toBe($this->admin->id);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.branch-areas.show', $area))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-areas/show')
            ->has('area.transfers', 1)
            ->where('area.transfers.0.from_branch_id', $this->fromBranch->id)
            ->where('area.transfers.0.to_branch_id', $this->toBranch->id)
            ->has('branches', 2)
        );
});

test('platform cannot add an area to a branch it already belongs to', function () {
    $area = BranchArea::factory()->forBranch($this->fromBranch)->create([
        'name' => 'Kuril',
    ]);

    $this->actingAs($this->admin, 'platform')
        ->from(route('platform.branch-areas.show', $area))
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->fromBranch))
        ->assertRedirect(route('platform.branch-areas.show', $area))
        ->assertSessionHasErrors(['to_branch_id']);

    expect($area->fresh()->branches()->count())->toBe(1)
        ->and(BranchAreaTransferHistory::query()->where('branch_area_id', $area->id)->exists())->toBeFalse();
});

test('platform user without edit-branches cannot add areas to branches', function () {
    $viewer = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $viewer->assignRole(RoleEnum::STAFF->value);
    $viewer->givePermissionTo(PermissionEnum::VIEW_BRANCHES->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $area = BranchArea::factory()->forBranch($this->fromBranch)->create();

    $this->actingAs($viewer, 'platform')
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertForbidden();
});

test('branch portal cannot add areas to other branches via platform transfer', function () {
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $area = BranchArea::factory()->forBranch($this->fromBranch)->create();

    $this->actingAs($branchAdmin, 'branch')
        ->post(route('platform.branch-areas.transfer', $area), areaTransferPayload($this->toBranch))
        ->assertRedirect();

    expect($area->fresh()->branches()->whereKey($this->toBranch->id)->exists())->toBeFalse();
});
