<?php

namespace App\Http\Controllers\Platform;

use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AssignFulfillmentDriverRequest;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Order\OrderAssignService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderAssignController extends Controller
{
    public function __construct(
        private OrderAssignService $orderAssign,
    ) {}

    public function candidates(Request $request, Order $order): JsonResponse
    {
        $fulfillment = $this->requireFulfillment($order);

        abort_unless($this->orderAssign->canAssign($fulfillment), 422, 'Cannot assign a driver after the order is delivered.');

        $search = trim((string) $request->input('filter.search', ''));

        return response()->json($this->orderAssign->paginatedCandidatesPayload(
            $fulfillment,
            $search !== '' ? $search : null,
            max(1, (int) $request->integer('page', 1)),
            (int) $request->integer('per_page', 20),
        ));
    }

    public function store(AssignFulfillmentDriverRequest $request, Order $order): RedirectResponse
    {
        $fulfillment = $this->requireFulfillment($order);

        $driver = User::query()->findOrFail($request->validated('driver_id'));
        $reason = $request->validated('reason');
        $advance = $request->validated('advance');

        $this->orderAssign->assign(
            $fulfillment,
            $driver,
            $request->user(),
            is_string($reason) ? $reason : null,
            is_string($advance) ? FulfillmentStatus::from($advance) : null,
        );

        Toast::success('Driver assigned.');

        return redirect()->route('platform.orders.show', $order);
    }

    private function requireFulfillment(Order $order): OrderFulfillment
    {
        abort_if($order->fulfillment === null, 404);

        return $order->fulfillment;
    }
}
