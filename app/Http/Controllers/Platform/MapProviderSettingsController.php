<?php

namespace App\Http\Controllers\Platform;

use App\Enums\MapCapability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ActivateMapProviderRequest;
use App\Http\Requests\Platform\UpdateMapProviderRequest;
use App\Http\Requests\Platform\UpdateMapSelectionRequest;
use App\Services\Maps\MapDashboardPayload;
use App\Services\Maps\MapProviderActivator;
use App\Services\Maps\MapProviderCatalog;
use App\Services\Maps\MapSettingsService;
use App\Services\Maps\MapUsageRecorder;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MapProviderSettingsController extends Controller
{
    public function __construct(
        private readonly MapSettingsService $settings,
        private readonly MapDashboardPayload $payload,
        private readonly MapProviderCatalog $catalog,
        private readonly MapUsageRecorder $usage,
        private readonly MapProviderActivator $activator,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('platform/settings/map-provider', $this->payload->build());
    }

    /**
     * The simple page: save the key, check it, and switch everything over in one step.
     */
    public function activate(ActivateMapProviderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $result = $this->activator->activate($data['provider'], $data, $request->user()?->id);

        $result['ok'] ? Toast::success($result['message']) : Toast::error($result['message']);

        return redirect()->route('platform.settings.map-provider.edit');
    }

    public function updateSelection(UpdateMapSelectionRequest $request): RedirectResponse
    {
        $this->settings->updateSelection($request->validated(), $request->user()?->id);

        Toast::success('Active map providers saved.');

        return redirect()->route('platform.settings.map-provider.edit');
    }

    public function updateProvider(UpdateMapProviderRequest $request, string $provider): RedirectResponse
    {
        $this->settings->updateProvider($provider, $request->validated(), $request->user()?->id);

        Toast::success(config("maps.providers.{$provider}.label").' settings saved.');

        return redirect()->route('platform.settings.map-provider.edit');
    }

    public function test(string $provider): RedirectResponse
    {
        abort_unless($this->settings->hasProvider($provider), 404);

        $instance = $this->catalog->make($provider);
        $label = config("maps.providers.{$provider}.label");

        try {
            $result = $instance->testConnection();

            if ($result['ok'] && $instance->supports(MapCapability::Geocoding)) {
                $this->usage->record($provider, MapCapability::Geocoding);
            }

            $result['ok']
                ? Toast::success("{$label}: {$result['message']}")
                : Toast::error("{$label}: {$result['message']}");
        } catch (Throwable $exception) {
            report($exception);
            Toast::error("{$label}: the connection test failed. Check the key and try again.");
        }

        return redirect()->route('platform.settings.map-provider.edit');
    }
}
