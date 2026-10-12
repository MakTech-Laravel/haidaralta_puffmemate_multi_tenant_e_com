<?php

namespace App\Http\Controllers\Driver;

use App\Actions\Order\PlaceDriverOrderAction;
use App\Enums\CustomerRegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\LookupPlaceOrderCustomerRequest;
use App\Http\Requests\Driver\PlaceDriverOrderRequest;
use App\Models\User;
use App\Services\Customer\CustomerResolutionService;
use App\Support\Driver\DriverPlaceOrderCatalog;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlaceOrderController extends Controller
{
    public function index(): Response
    {
        /** @var User $driver */
        $driver = auth('driver')->user();

        return Inertia::render(
            'driver/place-order/index',
            DriverPlaceOrderCatalog::forDriver($driver),
        );
    }

    public function lookup(LookupPlaceOrderCustomerRequest $request, CustomerResolutionService $customers): JsonResponse
    {
        $matches = $customers->search($request->string('q')->value());

        return response()->json([
            'customers' => $matches->map(fn (User $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'registration_status' => $customer->registration_status?->value
                    ?? CustomerRegistrationStatus::Provisional->value,
            ])->values()->all(),
        ]);
    }

    public function store(PlaceDriverOrderRequest $request, PlaceDriverOrderAction $action): RedirectResponse
    {
        /** @var User $driver */
        $driver = $request->user('driver');

        $order = $action->execute($driver, $request->validated());

        Toast::success('Order placed.');

        return redirect()->route('driver.orders.show', $order);
    }
}
