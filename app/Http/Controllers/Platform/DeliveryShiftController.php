<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreDeliveryShiftRequest;
use App\Http\Requests\Platform\UpdateDeliveryShiftRequest;
use App\Models\DeliveryShift;
use App\Services\Application\DeliveryShiftService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryShiftController extends Controller
{
    public function __construct(
        private readonly DeliveryShiftService $deliveryShifts,
    ) {}

    public function index(): Response
    {
        return Inertia::render('platform/delivery-shifts/index', [
            'shifts' => $this->deliveryShifts->indexPayload(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/delivery-shifts/form', [
            'shift' => null,
        ]);
    }

    public function store(StoreDeliveryShiftRequest $request): RedirectResponse
    {
        $this->deliveryShifts->create($request->validated(), $request->user('platform'));

        Toast::success('Delivery shift created.');

        return redirect()->route('platform.delivery-shifts.index');
    }

    public function show(DeliveryShift $deliveryShift): Response
    {
        return Inertia::render('platform/delivery-shifts/show', [
            'shift' => $this->deliveryShifts->detailPayload($deliveryShift),
        ]);
    }

    public function edit(DeliveryShift $deliveryShift): Response
    {
        return Inertia::render('platform/delivery-shifts/form', [
            'shift' => $this->deliveryShifts->formPayload($deliveryShift),
        ]);
    }

    public function update(UpdateDeliveryShiftRequest $request, DeliveryShift $deliveryShift): RedirectResponse
    {
        $this->deliveryShifts->update($deliveryShift, $request->validated());

        Toast::success('Delivery shift saved.');

        return redirect()->route('platform.delivery-shifts.index');
    }

    public function destroy(DeliveryShift $deliveryShift): RedirectResponse
    {
        $this->deliveryShifts->delete($deliveryShift);

        Toast::success('Delivery shift removed.');

        return redirect()->route('platform.delivery-shifts.index');
    }
}
