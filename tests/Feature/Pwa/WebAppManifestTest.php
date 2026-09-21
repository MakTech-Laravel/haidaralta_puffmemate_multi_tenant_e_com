<?php

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;

test('guest manifest uses origin start url and required chrome fields', function () {
    $response = $this->get(route('pwa.manifest'));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json');

    expect($response->headers->get('Cache-Control'))->toContain('no-cache');

    $manifest = $response->json();

    expect($manifest)
        ->id->toBe('/')
        ->lang->toBe('en-AU')
        ->name->toBe(config('pwa.name'))
        ->short_name->toBe(config('pwa.short_name'))
        ->start_url->toBe('/')
        ->scope->toBe('/')
        ->display->toBe('standalone')
        ->display_override->toBe(['standalone', 'browser'])
        ->orientation->toBe('any')
        ->prefer_related_applications->toBeFalse()
        ->and($manifest['launch_handler']['client_mode'])->toBe('navigate-existing')
        ->and($manifest['name'])->not->toContain('Staging')
        ->and($manifest['name'])->not->toContain('Local')
        ->and(collect($manifest['icons'])->pluck('sizes')->all())->toContain('192x192', '512x512')
        ->and(collect($manifest['shortcuts'])->pluck('url')->all())->toContain('/shop', '/cart', '/login');
});

test('authenticated portal manifest shortcuts replace login with dashboard', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);

    $urls = collect(
        $this->actingAs($user, 'platform')
            ->get(route('pwa.manifest'))
            ->json('shortcuts'),
    )->pluck('url');

    expect($urls->all())
        ->toContain('/shop', '/cart', '/platform/dashboard')
        ->and($urls->all())->not->toContain('/login');
});

test('platform manifest start url is the platform dashboard', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);

    $this->actingAs($user, 'platform')
        ->get(route('pwa.manifest'))
        ->assertOk()
        ->assertJsonPath('start_url', '/platform/dashboard')
        ->assertJsonPath('id', '/')
        ->assertJsonPath('name', config('pwa.name'));
});

test('branch manifest start url is the branch dashboard', function () {
    $branch = Branch::factory()->create(['slug' => 'sydney-harbour']);
    $user = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($user, 'branch')
        ->get(route('pwa.manifest'))
        ->assertOk()
        ->assertJsonPath('start_url', '/branch/sydney-harbour/dashboard');
});

test('driver manifest start url is the driver dashboard', function () {
    $user = User::factory()->create(['user_type' => UserType::DRIVER]);

    $this->actingAs($user, 'driver')
        ->get(route('pwa.manifest'))
        ->assertOk()
        ->assertJsonPath('start_url', '/driver/dashboard');
});

test('customer manifest start url stays on the storefront', function () {
    $user = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    $this->actingAs($user, 'customer')
        ->get(route('pwa.manifest'))
        ->assertOk()
        ->assertJsonPath('start_url', '/');
});

test('storefront home includes pwa head tags', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('apple-mobile-web-app-capable', false)
        ->assertSee('mobile-web-app-capable', false)
        ->assertSee('apple-touch-startup-image', false);
});

test('authenticated platform staff are redirected from the storefront home', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM]);

    $this->actingAs($user, 'platform')
        ->get(route('home'))
        ->assertRedirect('/platform/dashboard');
});

test('authenticated branch staff are redirected from the storefront home', function () {
    $branch = Branch::factory()->create(['slug' => 'parramatta']);
    $user = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($user, 'branch')
        ->get(route('home'))
        ->assertRedirect('/branch/parramatta/dashboard');
});

test('authenticated drivers are redirected from the storefront home', function () {
    $user = User::factory()->create(['user_type' => UserType::DRIVER]);

    $this->actingAs($user, 'driver')
        ->get(route('home'))
        ->assertRedirect('/driver/dashboard');
});

test('authenticated customers still see the storefront home', function () {
    $user = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    $this->actingAs($user, 'customer')
        ->get(route('home'))
        ->assertOk();
});
