<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateDeliveryMethodRequest;
use App\Models\DeliveryMethod;
use App\Services\Catalog\DeliverySettingsService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DeliverySettingsController extends Controller
{
    public function __construct(
        private readonly DeliverySettingsService $delivery,
    ) {}

    public function index(): Response
    {
        return Inertia::render('platform/delivery-settings/index', [
            'methods' => $this->delivery->indexPayload(),
        ]);
    }

    public function update(UpdateDeliveryMethodRequest $request, DeliveryMethod $deliveryMethod): RedirectResponse
    {
        $this->delivery->update($deliveryMethod, $request->validated());

        Toast::success('Delivery method saved.');

        return redirect()->route('platform.delivery-settings.index');
    }
}
