<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateGeneralSettingsRequest;
use App\Models\ApplicationSetting;
use App\Services\Application\ApplicationSettingsService;
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
                'date_format' => $current->date_format,
                'time_format' => $current->time_format,
                'logo_url' => $this->settings->mediaUrl(ApplicationSetting::LOGO_COLLECTION),
                'favicon_url' => $this->settings->mediaUrl(ApplicationSetting::FAVICON_COLLECTION),
                'pwa_icon_192_url' => $this->settings->mediaUrl(ApplicationSetting::PWA_192_COLLECTION),
                'pwa_icon_512_url' => $this->settings->mediaUrl(ApplicationSetting::PWA_512_COLLECTION),
                'pwa_maskable_url' => $this->settings->mediaUrl(ApplicationSetting::PWA_MASKABLE_COLLECTION),
                'apple_touch_icon_url' => $this->settings->mediaUrl(ApplicationSetting::APPLE_TOUCH_COLLECTION),
            ],
            'dateFormatPresets' => ApplicationSettingsService::DATE_FORMAT_PRESETS,
            'timeFormatPresets' => ApplicationSettingsService::TIME_FORMAT_PRESETS,
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
