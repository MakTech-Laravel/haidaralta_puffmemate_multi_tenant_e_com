<?php

use App\Enums\UserType;
use App\Models\Branch;
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

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('user searchable array casts filter fields to engine-safe types', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'branch_id' => null,
        'is_active' => true,
    ]);

    $payload = $user->toSearchableArray();

    expect($payload['id'])->toBeInt()
        ->and($payload['name'])->toBe($user->name)
        ->and($payload['email'])->toBe($user->email)
        ->and($payload['user_type'])->toBe(UserType::PLATFORM->value)
        ->and($payload['branch_id'])->toBeNull()
        ->and($payload['is_active'])->toBeTrue()
        ->and($payload['created_at'])->toBeInt();
});

test('user searchable array survives remember-token saves with timestamps disabled', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'branch_id' => null,
    ]);

    $user->timestamps = false;

    expect($user->toSearchableArray()['created_at'])->toBeInt()->toBeGreaterThan(0);
});

test('login with remember me does not error while syncing scout', function () {
    $this->post(route('login.store'), [
        'email' => UserSeeder::PLATFORM_ADMIN,
        'password' => UserSeeder::PLATFORM_ADMIN,
        'remember' => true,
    ])->assertRedirect('/platform/dashboard');

    $this->assertAuthenticated('platform');
});

test('branch searchable array includes slug and address', function () {
    $branch = Branch::factory()->create([
        'name' => 'Harbor',
        'slug' => 'harbor',
        'address' => '12 Dock Rd',
        'is_active' => true,
    ]);

    $payload = $branch->toSearchableArray();

    expect($payload['id'])->toBeInt()
        ->and($payload['name'])->toBe('Harbor')
        ->and($payload['slug'])->toBe('harbor')
        ->and($payload['address'])->toBe('12 Dock Rd')
        ->and($payload['is_active'])->toBeTrue()
        ->and($payload['created_at'])->toBeInt();
});

test('platform staff index search returns matching platform users', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    User::factory()->create([
        'name' => 'Zelda Unique Staff',
        'email' => 'zelda.unique.staff@test.com',
        'user_type' => UserType::PLATFORM,
        'is_active' => true,
    ]);

    User::factory()->create([
        'name' => 'Other Branch User',
        'email' => 'other.branch.user@test.com',
        'user_type' => UserType::BRANCH,
        'branch_id' => Branch::query()->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.staff.index', ['search' => 'Zelda Unique']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/staff/index')
            ->has('staff.data', 1)
            ->where('staff.data.0.email', 'zelda.unique.staff@test.com')
            ->where('filters.search', 'Zelda Unique'));
});

test('platform branches index search returns matching branches', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    Branch::factory()->create([
        'name' => 'Quokka Quarter',
        'slug' => 'quokka-quarter',
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.index', ['search' => 'Quokka']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/index')
            ->where('filters.search', 'Quokka')
            ->has('branches.data', 1)
            ->where('branches.data.0.slug', 'quokka-quarter'));
});
