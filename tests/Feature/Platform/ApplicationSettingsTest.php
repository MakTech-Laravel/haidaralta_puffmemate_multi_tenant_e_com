<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\ApplicationSetting;
use App\Models\PaymentGatewaySetting;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Application\SmtpSettingsService;
use App\Support\DateTimeFormat;
use App\Support\PlatformTeamResolver;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ApplicationSettingsSeeder::class);
    Cache::flush();
});

test('platform super admin can view general smtp and payment gateway settings', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.general.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('platform/settings/general'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.smtp.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('platform/settings/smtp'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.payment-gateway.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('platform/settings/payment-gateway'));
});

test('platform staff without manage-application-settings cannot open general settings', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.settings.general.edit'))
        ->assertForbidden();
});

test('platform admin can update general branding and formats', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.general.update'), [
            'app_name' => 'PuffMeMate Ops',
            'app_short_name' => 'PMM Ops',
            'short_description' => 'Multi-vendor tobacco marketplace',
            'theme_color' => '#C4472F',
            'background_color' => '#FFFFFF',
            'date_format' => 'd/m/Y',
            'time_format' => 'g:i A',
            'maintenance_mode' => false,
        ])
        ->assertRedirect(route('platform.settings.general.edit'));

    $settings = ApplicationSetting::query()->first();

    expect($settings)->not->toBeNull()
        ->and($settings->app_name)->toBe('PuffMeMate Ops')
        ->and($settings->date_format)->toBe('d/m/Y')
        ->and(config('app.name'))->toBe('PuffMeMate Ops');
});

test('datetime format helper uses viewer timezone from request', function () {
    app(ApplicationSettingsService::class)->update([
        'app_name' => 'PuffMeMate',
        'app_short_name' => 'PuffMeMate',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'theme_color' => '#C4472F',
        'background_color' => '#FFFFFF',
    ]);

    $request = Request::create('/');
    $request->headers->set(DateTimeFormat::VIEWER_TIMEZONE_HEADER, 'Asia/Dhaka');
    $this->app->instance('request', $request);

    $formatted = DateTimeFormat::dateTime(Carbon::parse('2026-01-01 00:00:00', 'UTC'));

    expect($formatted)->toBe('2026-01-01 06:00');
});

test('smtp settings keep password when blank and can send test email', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.smtp.update'), [
            'enabled' => true,
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer',
            'password' => 'secret-pass',
            'from_address' => 'noreply@example.com',
            'from_name' => 'PuffMeMate',
            'reply_to_address' => null,
        ])
        ->assertRedirect(route('platform.settings.smtp.edit'));

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.com')
        ->and(config('mail.from.address'))->toBe('noreply@example.com');

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.smtp.update'), [
            'enabled' => true,
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer',
            'password' => 'secret-pass',
            'from_address' => 'noreply@example.com',
            'from_name' => 'PuffMeMate',
            'reply_to_address' => 'support@example.com',
        ])
        ->assertRedirect(route('platform.settings.smtp.edit'));

    expect(config('mail.reply_to.address'))->toBe('support@example.com')
        ->and(config('mail.reply_to.name'))->toBe('PuffMeMate');

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.smtp.update'), [
            'enabled' => true,
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer',
            'password' => '',
            'from_address' => 'noreply@example.com',
            'from_name' => 'PuffMeMate',
            'reply_to_address' => 'support@example.com',
        ])
        ->assertRedirect(route('platform.settings.smtp.edit'));

    $settings = SmtpSetting::query()->first();

    expect($settings)->not->toBeNull()
        ->and($settings->password)->toBe('secret-pass');

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.smtp.update'), [
            'enabled' => true,
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer',
            'password' => '',
            'from_address' => 'noreply@example.com',
            'from_name' => 'PuffMeMate',
            'reply_to_address' => 'support@example.com',
        ])
        ->assertRedirect(route('platform.settings.smtp.edit'));

    expect(config('mail.reply_to.address'))->toBe('support@example.com');

    $sent = [];

    Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent): void {
        $sent[] = $event;
    });

    config(['mail.default' => 'array']);

    app(SmtpSettingsService::class)->sendTestEmail('tester@example.com');

    expect($sent)->not->toBeEmpty();

    $replyTo = $sent[0]->message->getReplyTo();
    $addresses = array_map(
        static fn ($address) => method_exists($address, 'getAddress')
            ? $address->getAddress()
            : (string) $address,
        array_values($replyTo),
    );

    expect($addresses)->toContain('support@example.com');
});

test('maintenance mode blocks storefront but allows platform settings', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $settings = app(ApplicationSettingsService::class);

    try {
        $settings->syncMaintenanceMode(true);

        $this->get(route('home'))
            ->assertStatus(503)
            ->assertSee('Temporarily offline', false)
            ->assertSee("We'll be", false)
            ->assertSee((string) config('app.name'), false);

        $this->actingAs($admin, 'platform')
            ->get(route('platform.settings.general.edit'))
            ->assertOk();
    } finally {
        $settings->syncMaintenanceMode(false);
    }
});

test('general settings share branding and formats on inertia pages', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.general.update'), [
            'app_name' => 'Brand Shared',
            'app_short_name' => 'Brand',
            'short_description' => 'Shared description for SEO',
            'theme_color' => '#112233',
            'background_color' => '#FFFFFF',
            'date_format' => 'd/m/Y',
            'time_format' => 'g:i A',
            'maintenance_mode' => false,
        ])
        ->assertRedirect(route('platform.settings.general.edit'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.general.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('name', 'Brand Shared')
            ->where('application.name', 'Brand Shared')
            ->where('application.shortName', 'Brand')
            ->where('application.description', 'Shared description for SEO')
            ->where('application.dateFormat', 'd/m/Y')
            ->where('application.timeFormat', 'g:i A')
            ->where('application.themeColor', '#112233')
            ->missing('application.timezone'))
        ->assertSee('name="theme-color" content="#112233"', false)
        ->assertSee('name="description" content="Shared description for SEO"', false);
});

test('payment gateway settings require at least one enabled method', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.payment-gateway.update'), [
            'methods' => [
                [
                    'key' => PaymentGatewaySetting::KEY_COD,
                    'label' => 'COD',
                    'description' => 'Cash',
                    'enabled' => false,
                ],
                [
                    'key' => PaymentGatewaySetting::KEY_POS,
                    'label' => 'POS',
                    'description' => 'Card',
                    'enabled' => false,
                ],
                [
                    'key' => PaymentGatewaySetting::KEY_PAYMENT_LINK,
                    'label' => 'Link',
                    'description' => 'Pay online',
                    'enabled' => false,
                ],
            ],
        ])
        ->assertSessionHasErrors('methods');
});

test('payment gateway settings update labels and feed enabled methods', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.settings.payment-gateway.update'), [
            'methods' => [
                [
                    'key' => PaymentGatewaySetting::KEY_COD,
                    'label' => 'Pay cash on delivery',
                    'description' => 'Cash only',
                    'enabled' => true,
                ],
                [
                    'key' => PaymentGatewaySetting::KEY_POS,
                    'label' => 'POS terminal',
                    'description' => 'Card machine',
                    'enabled' => false,
                ],
                [
                    'key' => PaymentGatewaySetting::KEY_PAYMENT_LINK,
                    'label' => 'Secure payment link',
                    'description' => 'Online link',
                    'enabled' => true,
                ],
            ],
        ])
        ->assertRedirect(route('platform.settings.payment-gateway.edit'));

    $enabled = app(PaymentGatewaySettingsService::class)->enabledMethods();

    expect($enabled)->toHaveCount(2)
        ->and($enabled[0]['id'])->toBe('cod')
        ->and($enabled[0]['title'])->toBe('Pay cash on delivery')
        ->and($enabled[1]['id'])->toBe('payment_link');
});

test('guests are redirected from application settings', function () {
    $this->get(route('platform.settings.general.edit'))
        ->assertRedirect(route('login'));
});

test('platform admin can upload and remove application branding media', function () {
    Storage::fake('public');
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $fields = [
        '_method' => 'PUT',
        'app_name' => 'PuffMeMate',
        'app_short_name' => 'PuffMeMate',
        'theme_color' => '#C4472F',
        'background_color' => '#FFFFFF',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'maintenance_mode' => false,
    ];

    $this->actingAs($admin, 'platform')
        ->post(route('platform.settings.general.update'), [
            ...$fields,
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            'pwa_icon_192' => UploadedFile::fake()->image('i192.png', 192, 192),
            'pwa_icon_512' => UploadedFile::fake()->image('i512.png', 512, 512),
            'pwa_maskable' => UploadedFile::fake()->image('mask.png', 512, 512),
            'apple_touch_icon' => UploadedFile::fake()->image('apple.png', 180, 180),
        ])
        ->assertRedirect(route('platform.settings.general.edit'));

    $settings = ApplicationSetting::query()->firstOrFail();

    foreach (ApplicationSetting::MEDIA_COLLECTIONS as $collection) {
        expect($settings->hasMedia($collection))->toBeTrue();
    }

    $service = app(ApplicationSettingsService::class);

    expect($service->sharedPayload()['logoUrl'])->toBe($settings->getFirstMediaUrl('logo'))
        ->and($service->faviconHref())->toBe($settings->getFirstMediaUrl('favicon'))
        ->and($service->appleTouchIconHref())->toBe($settings->getFirstMediaUrl('apple_touch'))
        ->and(config('pwa.icons.0.src'))->toBe($settings->getFirstMediaUrl('pwa_192'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.general.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('settings.logo_url', $settings->getFirstMediaUrl('logo'))
            ->where('settings.pwa_maskable_url', $settings->getFirstMediaUrl('pwa_maskable'))
            ->missing('settings.logo_path'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.settings.general.update'), [
            ...$fields,
            'remove_logo' => true,
            'remove_favicon' => true,
        ])
        ->assertRedirect(route('platform.settings.general.edit'));

    $settings = ApplicationSetting::query()->firstOrFail();

    expect($settings->hasMedia('logo'))->toBeFalse()
        ->and($settings->hasMedia('favicon'))->toBeFalse()
        ->and($settings->hasMedia('apple_touch'))->toBeTrue()
        ->and($service->sharedPayload()['logoUrl'])->toBeNull()
        ->and($service->faviconHref())->toBe('/favicon.ico');
});
