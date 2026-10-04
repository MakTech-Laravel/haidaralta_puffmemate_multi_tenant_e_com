<?php

namespace App\Services\Application;

use App\Models\ApplicationSetting;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ApplicationSettingsService
{
    public const CACHE_KEY = 'application_settings.current';

    public const MEDIA_CACHE_KEY = 'application_settings.media_urls';

    public function __construct(
        private readonly MediaUploadService $media,
    ) {}

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
        Cache::forget(self::MEDIA_CACHE_KEY);

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

        $icon192 = $this->mediaUrl(ApplicationSetting::PWA_192_COLLECTION)
            ?? '/pwa/pwa-192x192.png';
        $icon512 = $this->mediaUrl(ApplicationSetting::PWA_512_COLLECTION)
            ?? '/pwa/pwa-512x512.png';
        $maskable = $this->mediaUrl(ApplicationSetting::PWA_MASKABLE_COLLECTION)
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

        $settings->save();

        $uploads = [
            ApplicationSetting::LOGO_COLLECTION => [$logo, 'remove_logo'],
            ApplicationSetting::FAVICON_COLLECTION => [$favicon, 'remove_favicon'],
            ApplicationSetting::PWA_192_COLLECTION => [$pwaIcon192, 'remove_pwa_icon_192'],
            ApplicationSetting::PWA_512_COLLECTION => [$pwaIcon512, 'remove_pwa_icon_512'],
            ApplicationSetting::PWA_MASKABLE_COLLECTION => [$pwaMaskable, 'remove_pwa_maskable'],
            ApplicationSetting::APPLE_TOUCH_COLLECTION => [$appleTouchIcon, 'remove_apple_touch_icon'],
        ];

        foreach ($uploads as $collection => [$file, $removeKey]) {
            $this->media->syncSingle($settings, $collection, $file, (bool) ($data[$removeKey] ?? false));
        }
        if (array_key_exists('maintenance_mode', $data)) {
            $this->syncMaintenanceMode((bool) $data['maintenance_mode']);
        }

        return $this->refresh();
    }

    /**
     * @return array{
     *     name: string,
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
            'name' => $settings->app_name,
            'shortName' => $settings->app_short_name,
            'description' => $settings->short_description,
            'logoUrl' => $this->mediaUrl(ApplicationSetting::LOGO_COLLECTION),
            'faviconUrl' => $this->mediaUrl(ApplicationSetting::FAVICON_COLLECTION),
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

        return $this->mediaUrl(ApplicationSetting::FAVICON_COLLECTION) !== null;
    }

    public function faviconHref(): string
    {
        if (! $this->tableReady()) {
            return '/favicon.ico';
        }

        return $this->mediaUrl(ApplicationSetting::FAVICON_COLLECTION) ?? '/favicon.ico';
    }

    public function appleTouchIconHref(): string
    {
        if (! $this->tableReady()) {
            return '/apple-touch-icon.png';
        }

        return $this->mediaUrl(ApplicationSetting::APPLE_TOUCH_COLLECTION)
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

    /**
     * Public URL for an uploaded settings image, or null when none is set.
     */
    public function mediaUrl(string $collection): ?string
    {
        return $this->mediaUrls()[$collection] ?? null;
    }

    /**
     * @return array<string, string|null>
     */
    public function mediaUrls(): array
    {
        try {
            /** @var array<string, string|null> */
            $urls = Cache::rememberForever(self::MEDIA_CACHE_KEY, function (): array {
                $settings = $this->current();
                $urls = [];

                foreach (ApplicationSetting::MEDIA_COLLECTIONS as $collection) {
                    $urls[$collection] = $settings->mediaCollectionUrl($collection);
                }

                return $urls;
            });

            return $urls;
        } catch (Throwable) {
            return [];
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
     *     name: string,
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
            'name' => (string) config('app.name'),
            'shortName' => (string) config('pwa.short_name', config('app.name')),
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
        } catch (Throwable) {
            return false;
        }
    }
}
