<?php

namespace App\Http\Controllers\Driver;

use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderFulfillment;
use App\Support\Storefront\DemoCatalog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $driverId = auth()->id();

        $fulfillments = OrderFulfillment::query()
            ->where('driver_id', $driverId)
            ->with('order.items')
            ->latest('id')
            ->get();

        $active = $fulfillments->reject(
            fn (OrderFulfillment $fulfillment): bool => $fulfillment->status === FulfillmentStatus::Delivered,
        );
        $activeOrders = $active->values()->map(function (OrderFulfillment $fulfillment): array {
            $order = $fulfillment->order;
            $snapshot = $order->shipping_address_snapshot ?? [];

            return [
                'id' => $order->order_number,
                'orderNumber' => $order->order_number,
                'status' => $fulfillment->status === FulfillmentStatus::OutForDelivery ? 'out_for_delivery' : $fulfillment->status->value,
                'customerName' => $snapshot['name'] ?? 'Customer',
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
                'completedLabel' => null,
            ];
        })->all();

        $nextStop = $activeOrders[0] ?? null;

        return Inertia::render('driver/dashboard', [
            'summary' => [
                'activeCount' => count($activeOrders),
                'deliveredCount' => $fulfillments->where('status', FulfillmentStatus::Delivered)->count(),
                'assignedCount' => $fulfillments->count(),
                'nextStop' => $nextStop,
                'activeOrders' => array_slice($activeOrders, 0, 6),
            ],
            'hub' => [
                ...DemoCatalog::driverDashboardHub(),
                'nextCustomerPhone' => $nextStop['phone'] ?? null,
                'nextNotes' => $nextStop['notes'] ?? null,
            ],
        ]);
    }
}
