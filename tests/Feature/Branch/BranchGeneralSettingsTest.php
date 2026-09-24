<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
});

test('branch super admin can open general settings', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->get(route('branch.settings.general.edit', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/settings/general')
            ->where('branch.slug', BranchSeeder::SYDNEY_CBD)
            ->where('branch.name', $this->branch->name)
            ->has('branch.logo_url'));
});

test('branch admin can update general settings without changing slug code or status', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $originalSlug = $this->branch->slug;
    $originalCode = $this->branch->code;
    $originalStatus = $this->branch->status;
    $logo = UploadedFile::fake()->image('branch-logo.png', 120, 120);

    $this->actingAs($admin, 'branch')
        ->put(route('branch.settings.general.update', $this->branch), [
            'name' => 'Sydney CBD Updated',
            'slug' => 'hacked-slug',
            'code' => 'HACK',
            'status' => false,
            'phone' => '0298765432',
            'email' => 'sydney.updated@test.com',
            'address' => '1 Updated St',
            'opening_time' => '08:30',
            'closing_time' => '22:00',
            'logo' => $logo,
        ])
        ->assertRedirect(route('branch.settings.general.edit', $this->branch))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch settings updated.',
        ]);

    $this->branch->refresh();

    expect($this->branch->name)->toBe('Sydney CBD Updated')
        ->and($this->branch->slug)->toBe($originalSlug)
        ->and($this->branch->code)->toBe($originalCode)
        ->and($this->branch->status)->toBe($originalStatus)
        ->and($this->branch->phone)->toBe('+61298765432')
        ->and($this->branch->email)->toBe('sydney.updated@test.com')
        ->and($this->branch->address)->toBe('1 Updated St')
        ->and($this->branch->opening_time?->format('H:i'))->toBe('08:30')
        ->and($this->branch->closing_time?->format('H:i'))->toBe('22:00')
        ->and($this->branch->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($this->branch->logo);
});

test('branch manager can open general settings', function () {
    $manager = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $manager->assignRole(RoleEnum::MANAGER->value);

    $this->actingAs($manager, 'branch')
        ->get(route('branch.settings.general.edit', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('branch/settings/general'));
});

test('branch staff without manage branch settings cannot access general settings', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.settings.general.edit', $this->branch))
        ->assertForbidden();

    $this->actingAs($staff, 'branch')
        ->put(route('branch.settings.general.update', $this->branch), [
            'name' => 'Should Not Update',
        ])
        ->assertForbidden();
});
