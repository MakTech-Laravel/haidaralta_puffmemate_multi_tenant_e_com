<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateGeneralSettingsRequest;
use App\Services\Application\ApplicationSettingsService;
use App\Support\MediaUrl;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function __construct(
        private readonly ApplicationSettingsService $settings,
    ) {}

    public function edit(): Response
    {
        $current = $this->settings->current();

        return Inertia::render('platform/settings/general', [
            'settings' => [
                'app_name' => $current->app_name,
                'app_short_name' => $current->app_short_name,
                'short_description' => $current->short_description,
                'theme_color' => $current->theme_color,
                'background_color' => $current->background_color,
                'display_timezone' => $current->display_timezone,
                'date_format' => $current->date_format,
                'time_format' => $current->time_format,
                'logo_path' => $current->logo_path,
                'logo_url' => MediaUrl::resolve($current->logo_path, fallback: null),
                'favicon_path' => $current->favicon_path,
                'favicon_url' => MediaUrl::resolve($current->favicon_path, fallback: null),
                'pwa_icon_192_path' => $current->pwa_icon_192_path,
                'pwa_icon_192_url' => MediaUrl::resolve($current->pwa_icon_192_path, fallback: null),
                'pwa_icon_512_path' => $current->pwa_icon_512_path,
                'pwa_icon_512_url' => MediaUrl::resolve($current->pwa_icon_512_path, fallback: null),
                'pwa_maskable_path' => $current->pwa_maskable_path,
                'pwa_maskable_url' => MediaUrl::resolve($current->pwa_maskable_path, fallback: null),
                'apple_touch_icon_path' => $current->apple_touch_icon_path,
                'apple_touch_icon_url' => MediaUrl::resolve($current->apple_touch_icon_path, fallback: null),
            ],
            'dateFormatPresets' => ApplicationSettingsService::DATE_FORMAT_PRESETS,
            'timeFormatPresets' => ApplicationSettingsService::TIME_FORMAT_PRESETS,
            'timezones' => timezone_identifiers_list(),
            'runtime' => [
                'environment' => (string) config('app.env'),
                'debug' => (bool) config('app.debug'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'maintenance_mode' => $this->settings->isMaintenanceMode(),
            ],
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->settings->update(
            $request->validated(),
            $request->file('logo'),
            $request->file('favicon'),
            $request->file('pwa_icon_192'),
            $request->file('pwa_icon_512'),
            $request->file('pwa_maskable'),
            $request->file('apple_touch_icon'),
            $request->user()?->id,
        );

        Toast::success('General settings saved.');

        return redirect()->route('platform.settings.general.edit');
    }
}
