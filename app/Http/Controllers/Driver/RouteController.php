<?php

namespace App\Http\Controllers\Driver;

use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderFulfillment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouteController extends Controller
{
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
