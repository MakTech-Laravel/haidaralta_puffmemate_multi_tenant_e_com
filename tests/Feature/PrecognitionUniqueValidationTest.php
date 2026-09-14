<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Laravel\Fortify\Features;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

/**
 * @param  array<string, string>  $headers
 * @return array<string, string>
 */
function precognitionHeaders(string $only): array
{
    return [
        'Precognition' => 'true',
        'Precognition-Validate-Only' => $only,
        'Accept' => 'application/json',
    ];
}

test('precognitive staff store returns 204 for an available phone without creating a user', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = User::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('phone'))
        ->post(route('platform.staff.store'), [
            'phone' => '0412987654',
        ])
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');

    expect(User::query()->count())->toBe($before);
});

test('precognitive staff store returns 422 for a taken phone without creating a user', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = User::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('phone'))
        ->post(route('platform.staff.store'), [
            'phone' => $admin->phone,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone'])
        ->assertHeader('Precognition', 'true');

    expect(User::query()->count())->toBe($before);
});

test('precognitive staff update ignores the current users phone', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('phone'))
        ->put(route('platform.staff.update', $admin), [
            'phone' => $admin->phone,
        ])
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');
});

test('precognitive branch store returns 422 for a taken slug without creating a branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = Branch::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('slug'))
        ->post(route('platform.branches.store'), [
            'slug' => BranchSeeder::SYDNEY_CBD,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug'])
        ->assertHeader('Precognition', 'true');

    expect(Branch::query()->count())->toBe($before);
});

test('precognitive branch store returns 204 for an available code without creating a branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = Branch::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('code'))
        ->post(route('platform.branches.store'), [
            'code' => 'AVL-001',
        ])
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');

    expect(Branch::query()->count())->toBe($before);
});

test('precognitive branch update ignores the current branch slug and code', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('slug,code'))
        ->put(route('platform.branches.update', $branch), [
            'slug' => $branch->slug,
            'code' => $branch->code,
        ])
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');
});

test('precognitive role store returns 422 for a taken platform role name', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $before = Role::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders(precognitionHeaders('name'))
        ->post(route('platform.roles.store'), [
            'name' => RoleEnum::ADMIN->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name'])
        ->assertHeader('Precognition', 'true');

    expect(Role::query()->count())->toBe($before);
});

test('precognitive register returns 422 for a taken phone without creating a user', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $before = User::query()->count();

    $this->withHeaders(precognitionHeaders('phone'))
        ->post(route('register.store'), [
            'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone'])
        ->assertHeader('Precognition', 'true');

    expect(User::query()->count())->toBe($before)
        ->and(auth('customer')->check())->toBeFalse();
});

test('precognitive register returns 204 for an available phone without creating a user', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $before = User::query()->count();

    $this->withHeaders(precognitionHeaders('phone'))
        ->post(route('register.store'), [
            'phone' => '0412987654',
        ])
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');

    expect(User::query()->count())->toBe($before)
        ->and(auth('customer')->check())->toBeFalse();
});

test('precognitive profile update returns 422 when phone belongs to another user', function () {
    $user = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'phone_verified_at' => now(),
    ]);

    $this->actingAs($user, 'customer')
        ->withHeaders(precognitionHeaders('phone'))
        ->patch(route('profile.update'), [
            'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phone'])
        ->assertHeader('Precognition', 'true');

    expect($user->fresh()->phone)->not->toBe(UserSeeder::PLATFORM_SUPER_ADMIN_PHONE);
});

test('precognitive profile update returns 204 when keeping the current phone', function () {
    $user = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'phone_verified_at' => now(),
    ]);

    $this->actingAs($user, 'customer')
        ->withHeaders(precognitionHeaders('phone'))
        ->patch(route('profile.update'), [
            'phone' => $user->phone,
        ])
        ->assertNoContent()
        ->assertHeader('Precognition', 'true');
});
