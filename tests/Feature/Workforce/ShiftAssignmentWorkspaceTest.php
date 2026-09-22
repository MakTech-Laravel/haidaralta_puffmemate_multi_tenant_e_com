<?php

use App\Enums\ShiftAudience;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->travelTo('2026-09-22 10:00:00');
});

it('renders empty platform gate props before a branch and person are selected', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-assignments.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/shifts/assign')
            ->where('workspace', 'platform')
            ->where('selectedBranch', null)
            ->where('selectedPeopleKind', null)
            ->where('selectedPerson', null)
            ->where('calendar', null)
            ->has('templates', 0)
            ->has('assignmentGroups.data', 0));
});

it('loads a visible month and groups from shift days with workspace editability', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $platformTemplate = ShiftTemplate::factory()->forStaff()->create(['name' => 'Platform floor']);
    $branchTemplate = ShiftTemplate::factory()->forBranch($branch)->forStaff()->create(['name' => 'Branch floor']);
    UserShiftAssignment::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'shift_template_id' => $platformTemplate->id,
        'start_date' => '2026-09-23',
        'end_date' => '2026-09-24',
    ]);
    UserShiftAssignment::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'shift_template_id' => $branchTemplate->id,
        'start_date' => '2026-09-26',
        'end_date' => '2026-09-26',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-assignments.create', [
            'branch_id' => $branch->id,
            'people_kind' => 'staff',
            'user_id' => $staff->id,
            'month' => '2026-09',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedBranch.id', $branch->id)
            ->where('selectedPeopleKind', 'staff')
            ->where('selectedPerson.id', $staff->id)
            ->where('calendar.month', '2026-09')
            ->where('calendar.today', '2026-09-22')
            ->where('calendar.grid_start', '2026-08-31')
            ->where('calendar.grid_end', '2026-10-04')
            ->where('calendar.days.2026-09-23.0.editable', true)
            ->where('calendar.days.2026-09-26.0.editable', false)
            ->has('assignmentGroups.data', 2)
            ->where('assignmentGroups.data.0.name', 'Platform floor')
            ->where('assignmentGroups.data.0.date_count', 2)
            ->where('assignmentGroups.data.0.editable', true)
            ->where('assignmentGroups.data.1.editable', false)
            ->has('templates', 1)
            ->where('templates.0.id', $platformTemplate->id));
});

it('preserves platform branch-management and branch-portal ownership contexts', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $platformTemplate = ShiftTemplate::factory()->forStaff()->create(['name' => 'Network support']);
    $branchTemplate = ShiftTemplate::factory()->forBranch($branch)->forStaff()->create(['name' => 'Floor open']);
    ShiftTemplate::factory()->forBranch($branch)->create([
        'name' => 'Drivers only',
        'audience' => ShiftAudience::Driver,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.create', [
            'branch_id' => $branch->id,
            'people_kind' => 'staff',
            'user_id' => $staff->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspace', 'branch')
            ->has('templates', 1)
            ->where('templates.0.id', $branchTemplate->id));

    $this->actingAs($branchAdmin, 'branch')
        ->get(route('branch.shift-assignments.create', [
            'branch' => $branch,
            'people_kind' => 'staff',
            'user_id' => $staff->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('branch.id', $branch->id)
            ->where('selectedBranch.id', $branch->id)
            ->has('templates', 2)
            ->where('templates.0.id', fn (int $id): bool => in_array($id, [$platformTemplate->id, $branchTemplate->id], true)));
});

it('applies the atomic post contract and redirects with canonical query state', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->forStaff()->create();

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.shift-assignments.store'), [
            'branch_id' => $branch->id,
            'user_id' => $staff->id,
            'shift_template_id' => $template->id,
            'assign_dates' => ['2026-09-23', '2026-09-25'],
            'replace_day_ids' => [],
            'remove_day_ids' => [],
            'month' => '2026-09',
            'assignment_page' => 1,
        ]);

    $response->assertStatus(303)
        ->assertRedirectContains('branch_id='.$branch->id)
        ->assertRedirectContains('people_kind=staff')
        ->assertRedirectContains('user_id='.$staff->id)
        ->assertSessionHasNoErrors();

    expect(UserShiftDay::query()->where('user_id', $staff->id)->orderBy('duty_on')->pluck('duty_on')->map->toDateString()->all())
        ->toBe(['2026-09-23', '2026-09-25']);
});

it('returns schedule conflicts as validation errors without partial writes', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $template = ShiftTemplate::factory()->forStaff()->create();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.shift-assignments.create'))
        ->post(route('platform.shift-assignments.store'), [
            'branch_id' => $branch->id,
            'user_id' => $staff->id,
            'shift_template_id' => $template->id,
            'assign_dates' => ['2026-09-21', '2026-09-23'],
            'replace_day_ids' => [],
            'remove_day_ids' => [],
        ])
        ->assertRedirect(route('platform.shift-assignments.create'))
        ->assertSessionHasErrors(['schedule' => 'Past shifts cannot be changed.']);

    expect(UserShiftDay::query()->where('user_id', $staff->id)->exists())->toBeFalse();
});

it('redirects legacy edit urls into the canonical workspace state', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $platformTemplate = ShiftTemplate::factory()->create();
    $branchTemplate = ShiftTemplate::factory()->forBranch($branch)->create();
    $platformAssignment = UserShiftAssignment::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'shift_template_id' => $platformTemplate->id,
        'start_date' => '2026-10-03',
        'end_date' => '2026-10-03',
    ]);
    $branchAssignment = UserShiftAssignment::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'shift_template_id' => $branchTemplate->id,
        'start_date' => '2026-11-04',
        'end_date' => '2026-11-04',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-assignments.edit', $platformAssignment))
        ->assertRedirectContains('month=2026-10')
        ->assertRedirectContains('user_id='.$staff->id);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-shift-assignments.edit', $branchAssignment))
        ->assertRedirectContains('month=2026-11')
        ->assertRedirectContains('branch_id='.$branch->id);

    $this->actingAs($branchAdmin, 'branch')
        ->get(route('branch.shift-assignments.edit', [$branch, $branchAssignment]))
        ->assertRedirectContains('month=2026-11')
        ->assertRedirectContains('people_kind=staff');
});

it('does not select an inactive or wrong-kind person from query state', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $inactiveStaff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
        'is_active' => false,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.shift-assignments.create', [
            'branch_id' => $branch->id,
            'people_kind' => 'staff',
            'user_id' => $inactiveStaff->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedPerson', null)
            ->where('calendar', null)
            ->has('assignmentGroups.data', 0));
});
