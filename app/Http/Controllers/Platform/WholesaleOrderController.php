<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Order\PlaceWholesaleOrderAction;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreWholesaleOrderRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Order\BranchDeliveryQuote;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WholesaleOrderController extends Controller
{
    public function store(
        StoreWholesaleOrderRequest $request,
        PlaceWholesaleOrderAction $placeOrder,
    ): RedirectResponse {
        /** @var User $admin */
        $admin = $request->user('platform');
        $data = $request->validated();

        $order = $placeOrder->execute($admin, $data);

        Toast::success('Wholesale order '.$order->order_number.' placed.');

        if ($admin->can(PermissionEnum::MANAGE_ORDERS->value)) {
            return redirect()->route('platform.orders.show', $order);
        }

        if (! empty($data['wholesale_inquiry_id'])) {
            return redirect()->route('platform.wholesale-inquiries.show', (int) $data['wholesale_inquiry_id']);
        }

        return redirect()->route('platform.wholesale-inquiries.index');
    }

    public function deliveryQuote(Request $request, BranchDeliveryQuote $quotes): JsonResponse
    {
        $validated = $request->validate([
            'branch' => ['required', 'integer', 'exists:branches,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $branch = Branch::query()->findOrFail((int) $validated['branch']);

        return response()->json($quotes->quote($branch, $validated['latitude'], $validated['longitude']));
    }
}
