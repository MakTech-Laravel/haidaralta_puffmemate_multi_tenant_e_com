<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Order\DriverDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private DriverDeliveryService $deliveries) {}

    public function index(Request $request): Response
    {
        $orders = $this->assigned($request)
            ->reject(fn (OrderFulfillment $fulfillment): bool => $fulfillment->status === FulfillmentStatus::Delivered)
            ->map(fn (OrderFulfillment $fulfillment): array => $this->card($fulfillment))
            ->values()
            ->all();

        return Inertia::render('driver/orders/index', [
            'orders' => $orders,
            'status' => $request->session()->get('success'),
        ]);
    }

    public function history(Request $request): Response
    {
        $orders = $this->assigned($request)
            ->filter(fn (OrderFulfillment $fulfillment): bool => $fulfillment->status === FulfillmentStatus::Delivered)
            ->map(fn (OrderFulfillment $fulfillment): array => $this->card($fulfillment))
            ->values()
            ->all();

        return Inertia::render('driver/history', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $fulfillment = $this->owned($request, $order);

        return Inertia::render('driver/orders/show', [
            'order' => $this->detail($fulfillment),
        ]);
    }

    public function chat(Request $request, Order $order): Response
    {
        $fulfillment = $this->owned($request, $order);

        return Inertia::render('driver/orders/chat', [
            'order' => $this->detail($fulfillment),
            'messages' => [],
            'viewerId' => (int) $request->user()->id,
            'canSend' => false,
        ]);
    }

    public function pickUp(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->pickUp($this->owned($request, $order), $request->user());

        return back()->with('success', 'Order picked up.');
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->startDelivery($this->owned($request, $order), $request->user());

        return back()->with('success', 'Delivery started. Ask the customer for their code.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,}$/'],
        ]);

        $this->deliveries->complete($this->owned($request, $order), $request->user(), $request->string('otp')->toString());

        return to_route('driver.orders.index')->with('success', 'Order marked as delivered.');
    }

    public function fail(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->deliveries->fail(
            $this->owned($request, $order),
            $request->user(),
            DeliveryFailureReason::from($data['reason']),
            $data['note'] ?? null,
        );

        return back()->with('success', 'Delivery marked unsuccessful.');
    }

    /**
     * @return Collection<int, OrderFulfillment>
     */
    private function assigned(Request $request)
    {
        return OrderFulfillment::query()
            ->where('driver_id', $request->user()->id)
            ->with(['order.items'])
            ->latest('id')
            ->get();
    }

    private function owned(Request $request, Order $order): OrderFulfillment
    {
        $fulfillment = $order->fulfillment;
        $driverId = (int) $request->user()->id;
        $assigned = $fulfillment !== null && (int) $fulfillment->driver_id === $driverId;
        $ownSale = $order->source === OrderSource::DriverSale && (int) $order->placed_by === $driverId;

        abort_unless($fulfillment !== null && ($assigned || $ownSale), 404);

        $fulfillment->loadMissing('order.items');

        return $fulfillment;
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
            'status' => $this->driverStatus($fulfillment->status),
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

    /**
     * @return array<string, mixed>
     */
    private function detail(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $snapshot = $order->shipping_address_snapshot ?? [];
        $card = $this->card($fulfillment);

        return [
            ...$card,
            'statusLabel' => $fulfillment->status->label(),
            'demoOtpHint' => '',
            'canPickUp' => $fulfillment->status === FulfillmentStatus::Assigned,
            'canStart' => in_array($fulfillment->status, [FulfillmentStatus::PickedUp, FulfillmentStatus::DeliveryFailed], true),
            'canDeliver' => $fulfillment->status === FulfillmentStatus::OutForDelivery,
            'canFail' => $fulfillment->status === FulfillmentStatus::OutForDelivery,
            'failureReasons' => array_map(
                fn (DeliveryFailureReason $reason): array => [
                    'value' => $reason->value,
                    'label' => $reason->label(),
                ],
                DeliveryFailureReason::cases(),
            ),
            'shippingAddress' => [
                'line1' => $snapshot['address'] ?? '',
                'line2' => null,
                'city' => $snapshot['city'] ?? '',
                'region' => '',
                'postalCode' => $snapshot['postal_code'] ?? '',
                'country' => 'Australia',
            ],
            'products' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name_snapshot,
                'quantityLabel' => (string) $item->quantity,
                'priceLabel' => '$'.number_format((float) $item->subtotal, 2),
            ])->all(),
        ];
    }

    private function driverStatus(FulfillmentStatus $status): string
    {
        return match ($status) {
            FulfillmentStatus::Delivered => 'delivered',
            FulfillmentStatus::OutForDelivery => 'out_for_delivery',
            default => $status->value,
        };
    }
}
