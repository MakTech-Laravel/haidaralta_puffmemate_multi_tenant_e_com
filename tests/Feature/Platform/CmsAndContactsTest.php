<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\ContactSetting;
use App\Models\CmsPage;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ApplicationSettingsSeeder::class);
    $this->seed(CmsPageSeeder::class);
    Cache::flush();
});

test('platform admin can view and update contact settings', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.contacts.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('platform/settings/contacts'));

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.contacts.update'), [
            'phone' => '+61 400 000 000',
            'fax' => '+61 2 0000 0000',
            'email' => 'hello@example.com',
            'whatsapp' => '+61 400 000 001',
            'address' => 'Sydney NSW',
            'map_embed_url' => 'https://maps.example.com',
            'socials' => [
                ['key' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/puff', 'icon' => 'Instagram'],
            ],
        ])
        ->assertRedirect(route('platform.settings.contacts.edit'));

    $settings = ContactSetting::query()->first();

    expect($settings)->not->toBeNull()
        ->and($settings->phone)->toBe('+61 400 000 000')
        ->and($settings->email)->toBe('hello@example.com')
        ->and($settings->socials[0]['key'])->toBe('instagram')
        ->and($settings->socials[0]['icon'])->toBe('Instagram');
});

test('platform staff without manage-application-settings cannot open contacts', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.settings.contacts.edit'))
        ->assertForbidden();
});

test('platform admin can list and update cms pages', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.pages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/pages/index')
            ->has('pages', 6));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.pages.edit', 'about'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('platform/pages/edit'));

    $about = CmsPage::query()->where('slug', 'about')->firstOrFail();
    $payload = $about->payload;
    $payload['hero']['title'] = 'About Updated';

    $this->actingAs($admin, 'platform')
        ->put(route('platform.pages.update', 'about'), [
            'title' => 'About Updated',
            'meta_title' => 'About Updated',
            'meta_description' => 'Updated description',
            'is_published' => true,
            'payload' => $payload,
        ])
        ->assertRedirect(route('platform.pages.edit', 'about'));

    expect(CmsPage::query()->where('slug', 'about')->value('title'))->toBe('About Updated');
});

test('platform admin can update privacy date and about hero image', function () {
    Storage::fake('public');
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $privacy = CmsPage::query()->where('slug', 'privacy')->firstOrFail();
    $payload = $privacy->payload;
    $payload['updatedAt'] = '2026-03-15';

    $this->actingAs($admin, 'platform')
        ->put(route('platform.pages.update', 'privacy'), [
            'title' => $privacy->title,
            'meta_title' => $privacy->meta_title,
            'meta_description' => $privacy->meta_description,
            'is_published' => true,
            'payload' => $payload,
        ])
        ->assertRedirect(route('platform.pages.edit', 'privacy'));

    $saved = CmsPage::query()->where('slug', 'privacy')->firstOrFail();
    expect($saved->payload['updatedAt'])->toBe('2026-03-15');

    $about = CmsPage::query()->where('slug', 'about')->firstOrFail();
    $aboutPayload = $about->payload;
    $file = UploadedFile::fake()->image('hero.jpg', 800, 400);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.pages.update', 'about'), [
            '_method' => 'PUT',
            'title' => $about->title,
            'meta_title' => $about->meta_title,
            'meta_description' => $about->meta_description,
            'is_published' => true,
            'payload' => $aboutPayload,
            'hero_image' => $file,
        ])
        ->assertRedirect(route('platform.pages.edit', 'about'));

    $aboutFresh = CmsPage::query()->where('slug', 'about')->firstOrFail();
    expect($aboutFresh->payload['hero']['image'])->toBeString()
        ->and($aboutFresh->payload['hero']['image'])->toContain('cms/about/hero');
});

test('cms edit payload includes image_url for file upload preview', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.pages.edit', 'about'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/pages/edit')
            ->has('page.payload.hero.image')
            ->has('page.payload.hero.image_url'));
});

test('platform staff without manage-pages cannot open pages cms', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.pages.index'))
        ->assertForbidden();
});
