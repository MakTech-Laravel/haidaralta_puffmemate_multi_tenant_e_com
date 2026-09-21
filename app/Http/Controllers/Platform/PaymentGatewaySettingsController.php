<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePaymentGatewaySettingsRequest;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentGatewaySettingsController extends Controller
{
    public function __construct(
        private readonly PaymentGatewaySettingsService $settings,
    ) {}

    public function edit(): Response
    {
        $methods = $this->settings->all()
            ->map(fn ($method): array => [
                'key' => $method->key,
                'label' => $method->label,
                'description' => $method->description,
                'enabled' => $method->enabled,
            ])
            ->values()
            ->all();

        return Inertia::render('platform/settings/payment-gateway', [
            'methods' => $methods,
        ]);
    }

    public function update(UpdatePaymentGatewaySettingsRequest $request): RedirectResponse
    {
        $this->settings->sync($request->validated('methods'));

        Toast::success('Payment gateway settings saved.');

        return redirect()->route('platform.settings.payment-gateway.edit');
    }
}
