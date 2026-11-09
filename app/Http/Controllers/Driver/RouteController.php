<?php

namespace App\Http\Controllers\Driver;

use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Driver\DriverLocationService;
use App\Services\Maps\Exceptions\MapProviderUnavailableException;
use App\Services\Maps\MapManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
    public function __construct(
        private readonly MapManager $maps,
        private readonly DriverLocationService $locations,
    ) {}

    /**
     * Sequenced assigned stops for this shift.
     */
    public function index(Request $request): Response
    {
        $fulfillments = OrderFulfillment::query()
            ->where('driver_id', $request->user()->id)
            ->with('order.items')
            ->latest('id')
            ->get();

        $remaining = $fulfillments
            ->reject(fn (OrderFulfillment $fulfillment): bool => $fulfillment->status === FulfillmentStatus::Delivered)
            ->map(fn (OrderFulfillment $fulfillment): array => $this->card($fulfillment))
            ->values()
            ->all();
        $completed = $fulfillments
            ->filter(fn (OrderFulfillment $fulfillment): bool => $fulfillment->status === FulfillmentStatus::Delivered)
            ->map(fn (OrderFulfillment $fulfillment): array => $this->card($fulfillment))
            ->values()
            ->all();
        $highlightId = $request->query('highlight');

        return Inertia::render('driver/route/index', [
            'remaining' => $remaining,
            'completed' => $completed,
            'nextStop' => $remaining[0] ?? null,
            'highlightId' => is_string($highlightId) && $highlightId !== '' ? $highlightId : null,
        ]);
    }

    /**
     * Road route from the driver's last known position to one of their stops.
     *
     * Requested only when the driver taps "Show road route", and cached per rounded start and
     * end point, so the paid routing API is never called on every position update.
     */
    public function directions(Request $request, Order $order): JsonResponse
    {
        $driver = $request->user();

        $assigned = OrderFulfillment::query()
            ->where('order_id', $order->id)
            ->where('driver_id', $driver->id)
            ->exists();

        abort_unless($assigned, 404);

        if ($order->latitude === null || $order->longitude === null) {
            return response()->json(['message' => 'This stop has no map pin.'], 422);
        }

        $position = $this->locations->lastPosition($driver->id);

        if ($position === null) {
            return response()->json(['message' => 'Your location is not available yet.'], 409);
        }

        try {
            $route = $this->maps->route(
                [$position['lat'], $position['lng']],
                [(float) $order->latitude, (float) $order->longitude],
            );
        } catch (MapProviderUnavailableException) {
            return response()->json(['message' => 'Road routing is not available right now.'], 503);
        }

        return response()->json(['route' => $route?->toArray()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $snapshot = $order->shipping_address_snapshot ?? [];

        return [
            'id' => $order->order_number,
            'orderNumber' => $order->order_number,
            'status' => $fulfillment->status === FulfillmentStatus::OutForDelivery
                ? 'out_for_delivery'
                : $fulfillment->status->value,
            'customerName' => $snapshot['name'] ?? ($order->customer?->name ?? 'Customer'),
            'areaId' => '',
            'areaLabel' => $snapshot['city'] ?? 'Delivery',
            'addressSummary' => $snapshot['address'] ?? '',
            'windowLabel' => $order->time_slot ?? 'Anytime',
            'productCount' => (int) $order->items->sum('quantity'),
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'phone' => $snapshot['phone'] ?? '',
            'latitude' => $order->latitude !== null ? (float) $order->latitude : null,
            'longitude' => $order->longitude !== null ? (float) $order->longitude : null,
            'notes' => $order->notes,
            'unitLabel' => null,
            'productPreview' => $order->items->pluck('product_name_snapshot')->take(3)->all(),
            'paymentMethod' => $order->payment_method === 'pos' ? 'paid' : 'cod',
            'etaLabel' => null,
            'completedLabel' => $fulfillment->status === FulfillmentStatus::Delivered
                ? $fulfillment->updated_at?->format('d M Y')
                : null,
        ];
    }
}
