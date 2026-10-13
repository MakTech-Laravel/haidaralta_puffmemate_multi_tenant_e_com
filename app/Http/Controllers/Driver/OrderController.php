<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderReturnType;
use App\Enums\OrderSource;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Conversation\ConversationService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\OrderReturnService;
use App\Support\ConversationPayload;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private DriverDeliveryService $deliveries,
        private ConversationService $conversations,
        private OrderReturnService $returns,
    ) {}

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
        abort_unless($fulfillment->status->allowsOrderChat(), 404);

        $conversation = $order->conversation;

        return Inertia::render('driver/orders/chat', [
            'order' => $this->detail($fulfillment),
            'messages' => $conversation === null
                ? []
                : ConversationPayload::messages(
                    $conversation->messages()->with(['sender:id,name', 'sender.media', 'attachments'])->orderBy('id')->get(),
                ),
            'viewerId' => (int) $request->user()->id,
            'canSend' => $conversation !== null
                && ! $conversation->isClosed()
                && ! $fulfillment->status->isTerminal(),
        ]);
    }

    public function storeMessage(Request $request, Order $order): RedirectResponse
    {
        $fulfillment = $this->owned($request, $order);
        abort_unless($fulfillment->status->allowsOrderChat() && ! $fulfillment->status->isTerminal(), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->conversations->sendMessage($this->thread($order), $request->user(), $validated['body']);
        Toast::success('Message sent.');

        return back();
    }

    public function pickUp(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->pickUp($this->owned($request, $order), $request->user());

        return back()->with('success', 'Order picked up.');
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->startDelivery($this->owned($request, $order), $request->user());

        return back()->with('success', 'Delivery started.');
    }

    public function arrive(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->arrive($this->owned($request, $order), $request->user());

        return back()->with('success', 'Marked as arrived.');
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
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->deliveries->fail(
            $this->owned($request, $order),
            $request->user(),
            DeliveryFailureReason::from($data['reason']),
            $data['note'] ?? null,
        );

        return back()->with('success', 'Customer marked unavailable.');
    }

    public function beginReturn(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->beginReturn($this->owned($request, $order), $request->user());

        return back()->with('success', 'Goods marked as returning to the branch.');
    }

    public function storeReturn(Request $request, Order $order): RedirectResponse
    {
        $this->owned($request, $order);
        $data = $request->validate([
            'type' => ['required', 'in:return,refund'],
            'reason' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->returns->request(
            $order,
            $request->user(),
            OrderReturnType::from($data['type']),
            $data['reason'],
            $data['note'] ?? null,
        );

        return back()->with('success', 'Return request sent.');
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
            'company' => $snapshot['company'] ?? null,
            'source' => $order->source->value,
            'sourceLabel' => $order->source->label(),
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
            'canChat' => $fulfillment->status->allowsOrderChat(),
            'canPickUp' => $fulfillment->status === FulfillmentStatus::Assigned,
            'canStart' => $fulfillment->status === FulfillmentStatus::PickedUp,
            'canArrive' => $fulfillment->status === FulfillmentStatus::OutForDelivery,
            'canDeliver' => $fulfillment->status === FulfillmentStatus::Arrived,
            'canFail' => $fulfillment->status === FulfillmentStatus::Arrived,
            'canReturn' => $fulfillment->status === FulfillmentStatus::DeliveryFailed,
            'canRequestReturn' => in_array($fulfillment->status, [FulfillmentStatus::Arrived, FulfillmentStatus::Delivered], true)
                && $order->returns()->where('status', 'requested')->doesntExist(),
            'returnRequest' => $this->returns->payload($order),
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

    private function thread(Order $order): Conversation
    {
        $conversation = $order->conversation;

        abort_if($conversation === null, 404);

        return $conversation;
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
