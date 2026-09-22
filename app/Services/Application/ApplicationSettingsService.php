<?php

namespace App\Services\Application;

use App\Models\ApplicationSetting;
use App\Support\MediaUrl;
use App\Support\PublicDiskMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class ApplicationSettingsService
{
    public const CACHE_KEY = 'application_settings.current';

    public const MEDIA_DIRECTORY = 'application';

    /**
     * @var list<string>
     */
    public const DATE_FORMAT_PRESETS = [
        'Y-m-d',
        'd/m/Y',
        'm/d/Y',
        'd M Y',
        'M d, Y',
        'd-m-Y',
    ];

    /**
     * @var list<string>
     */
    public const TIME_FORMAT_PRESETS = [
        'H:i',
        'H:i:s',
        'h:i A',
        'g:i A',
    ];

    public function current(): ApplicationSetting
    {
        /** @var array<string, mixed> $attributes */
        $attributes = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return $this->resolveOrCreate()->getAttributes();
        });

        return (new ApplicationSetting)->newFromBuilder($attributes);
    }

    public function refresh(): ApplicationSetting
    {
        Cache::forget(self::CACHE_KEY);

        $settings = $this->resolveOrCreate();
        Cache::forever(self::CACHE_KEY, $settings->getAttributes());
        $this->bootRuntimeOverlay($settings);

        return $settings;
    }

    public function bootRuntimeOverlay(?ApplicationSetting $settings = null): void
    {
        if (! $this->tableReady()) {
            return;
        }

        $settings ??= $this->current();

        Config::set('app.name', $settings->app_name);
        Config::set('pwa.name', $settings->app_name);
        Config::set('pwa.short_name', $settings->app_short_name);
        Config::set('pwa.theme_color', $settings->theme_color);
        Config::set('pwa.background_color', $settings->background_color);

        $icons = [];

        $icon192 = MediaUrl::resolve($settings->pwa_icon_192_path, fallback: null)
            ?? '/pwa/pwa-192x192.png';
        $icon512 = MediaUrl::resolve($settings->pwa_icon_512_path, fallback: null)
            ?? '/pwa/pwa-512x512.png';
        $maskable = MediaUrl::resolve($settings->pwa_maskable_path, fallback: null)
            ?? '/pwa/maskable-icon-512x512.png';

        $icons[] = [
            'src' => $icon192,
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ];
        $icons[] = [
            'src' => $icon512,
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ];
        $icons[] = [
            'src' => $maskable,
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ];

        Config::set('pwa.icons', $icons);
    }

    /**
     * @param  array{
     *     app_name: string,
     *     app_short_name: string,
     *     short_description?: string|null,
     *     theme_color?: string,
     *     background_color?: string,
     *     date_format: string,
     *     time_format: string,
     *     remove_logo?: bool,
     *     remove_favicon?: bool,
     *     remove_pwa_icon_192?: bool,
     *     remove_pwa_icon_512?: bool,
     *     remove_pwa_maskable?: bool,
     *     remove_apple_touch_icon?: bool,
     *     maintenance_mode?: bool,
     * }  $data
     */
    public function update(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $favicon = null,
        ?UploadedFile $pwaIcon192 = null,
        ?UploadedFile $pwaIcon512 = null,
        ?UploadedFile $pwaMaskable = null,
        ?UploadedFile $appleTouchIcon = null,
        ?int $updatedBy = null,
    ): ApplicationSetting {
        $settings = $this->current();

        $settings->fill([
            'app_name' => $data['app_name'],
            'app_short_name' => $data['app_short_name'],
            'short_description' => $data['short_description'] ?? null,
            'theme_color' => $data['theme_color'] ?? $settings->theme_color,
            'background_color' => $data['background_color'] ?? $settings->background_color,
            'date_format' => $data['date_format'],
            'time_format' => $data['time_format'],
            'updated_by' => $updatedBy,
        ]);

        $settings->logo_path = PublicDiskMedia::replace(
            $settings->logo_path,
            $logo,
            self::MEDIA_DIRECTORY.'/logo',
            (bool) ($data['remove_logo'] ?? false),
        );
        $settings->favicon_path = PublicDiskMedia::replace(
            $settings->favicon_path,
            $favicon,
            self::MEDIA_DIRECTORY.'/favicon',
            (bool) ($data['remove_favicon'] ?? false),
        );
        $settings->pwa_icon_192_path = PublicDiskMedia::replace(
            $settings->pwa_icon_192_path,
            $pwaIcon192,
            self::MEDIA_DIRECTORY.'/pwa',
            (bool) ($data['remove_pwa_icon_192'] ?? false),
        );
        $settings->pwa_icon_512_path = PublicDiskMedia::replace(
            $settings->pwa_icon_512_path,
            $pwaIcon512,
            self::MEDIA_DIRECTORY.'/pwa',
            (bool) ($data['remove_pwa_icon_512'] ?? false),
        );
        $settings->pwa_maskable_path = PublicDiskMedia::replace(
            $settings->pwa_maskable_path,
            $pwaMaskable,
            self::MEDIA_DIRECTORY.'/pwa',
            (bool) ($data['remove_pwa_maskable'] ?? false),
        );
        $settings->apple_touch_icon_path = PublicDiskMedia::replace(
            $settings->apple_touch_icon_path,
            $appleTouchIcon,
            self::MEDIA_DIRECTORY.'/pwa',
            (bool) ($data['remove_apple_touch_icon'] ?? false),
        );

        $settings->save();

        if (array_key_exists('maintenance_mode', $data)) {
            $this->syncMaintenanceMode((bool) $data['maintenance_mode']);
        }

        return $this->refresh();
    }

    /**
     * @return array{
     *     shortName: string,
     *     description: string|null,
     *     logoUrl: string|null,
     *     faviconUrl: string|null,
     *     dateFormat: string,
     *     timeFormat: string,
     *     themeColor: string,
     *     backgroundColor: string
     * }
     */
    public function sharedPayload(): array
    {
        if (! $this->tableReady()) {
            return $this->fallbackSharedPayload();
        }

        $settings = $this->current();

        return [
            'shortName' => $settings->app_short_name,
            'description' => $settings->short_description,
            'logoUrl' => MediaUrl::resolve($settings->logo_path, fallback: null),
            'faviconUrl' => MediaUrl::resolve($settings->favicon_path, fallback: null),
            'dateFormat' => $settings->date_format,
            'timeFormat' => $settings->time_format,
            'themeColor' => $settings->theme_color,
            'backgroundColor' => $settings->background_color,
        ];
    }

    public function hasCustomFavicon(): bool
    {
        if (! $this->tableReady()) {
            return false;
        }

        return filled($this->current()->favicon_path);
    }

    public function faviconHref(): string
    {
        if (! $this->tableReady()) {
            return '/favicon.ico';
        }

        return MediaUrl::resolve($this->current()->favicon_path, fallback: null) ?? '/favicon.ico';
    }

    public function appleTouchIconHref(): string
    {
        if (! $this->tableReady()) {
            return '/apple-touch-icon.png';
        }

        return MediaUrl::resolve($this->current()->apple_touch_icon_path, fallback: null)
            ?? '/apple-touch-icon.png';
    }

    public function shortDescription(): ?string
    {
        if (! $this->tableReady()) {
            return null;
        }

        $description = $this->current()->short_description;

        return filled($description) ? (string) $description : null;
    }

    public function isMaintenanceMode(): bool
    {
        return app()->maintenanceMode()->active();
    }

    public function syncMaintenanceMode(bool $enabled): void
    {
        $maintenance = app()->maintenanceMode();

        if ($enabled && ! $maintenance->active()) {
            $secret = filled(config('app.maintenance_secret'))
                ? (string) config('app.maintenance_secret')
                : null;

            $maintenance->activate([
                // Keep platform ops reachable so settings can be turned back off.
                'except' => [
                    'platform',
                    'platform/*',
                    'up',
                ],
                'redirect' => null,
                'retry' => 60,
                'refresh' => 15,
                'secret' => $secret,
                'status' => 503,
                'template' => null,
            ]);

            return;
        }

        if (! $enabled && $maintenance->active()) {
            $maintenance->deactivate();
        }
    }

    private function resolveOrCreate(): ApplicationSetting
    {
        $existing = ApplicationSetting::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return ApplicationSetting::query()->create([
            'app_name' => (string) config('app.name', 'PuffMeMate'),
            'app_short_name' => (string) config('pwa.short_name', config('app.name', 'PuffMeMate')),
            'short_description' => null,
            'theme_color' => (string) config('pwa.theme_color', '#C4472F'),
            'background_color' => (string) config('pwa.background_color', '#FFFFFF'),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);
    }

    /**
     * @return array{
     *     shortName: string,
     *     description: null,
     *     logoUrl: null,
     *     faviconUrl: null,
     *     dateFormat: string,
     *     timeFormat: string,
     *     themeColor: string,
     *     backgroundColor: string
     * }
     */
    private function fallbackSharedPayload(): array
    {
        return [
            'shortName' => (string) config('pwa.short_name', config('app.name', 'PuffMeMate')),
            'description' => null,
            'logoUrl' => null,
            'faviconUrl' => null,
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
            'themeColor' => (string) config('pwa.theme_color', '#C4472F'),
            'backgroundColor' => (string) config('pwa.background_color', '#FFFFFF'),
        ];
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('application_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
