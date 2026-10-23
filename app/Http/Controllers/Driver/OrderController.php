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
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Order\OrderReturnService;
use App\Support\ConversationPayload;
use App\Support\Toast;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Field queue order: the stop at the door first, untouched pickups last.
     *
     * @var list<FulfillmentStatus>
     */
    private const FIELD_QUEUE = [
        FulfillmentStatus::Arrived,
        FulfillmentStatus::OutForDelivery,
        FulfillmentStatus::DeliveryFailed,
        FulfillmentStatus::Returning,
        FulfillmentStatus::PickedUp,
        FulfillmentStatus::Assigned,
    ];

    /**
     * @var list<FulfillmentStatus>
     */
    private const HISTORY_STATUSES = [
        FulfillmentStatus::Delivered,
        FulfillmentStatus::Returned,
        FulfillmentStatus::Cancelled,
    ];

    public function __construct(
        private DriverDeliveryService $deliveries,
        private ConversationService $conversations,
        private OrderReturnService $returns,
        private OrderPlacementNotifier $placements,
    ) {}

    /**
     * Today's run: every stop the driver still holds plus drops completed today.
     */
    public function index(Request $request): Response
    {
        $fulfillments = OrderFulfillment::query()
            ->where('driver_id', $request->user()->id)
            ->where(fn (Builder $query) => $query
                ->whereIn('status', array_map(
                    fn (FulfillmentStatus $status): string => $status->value,
                    FulfillmentStatus::driverWorkload(),
                ))
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', FulfillmentStatus::Delivered->value)
                    ->where('updated_at', '>=', today())))
            ->with(['order.items', 'order.customer:id,name'])
            ->oldest('id')
            ->get();

        [$active, $delivered] = $fulfillments->partition(
            fn (OrderFulfillment $fulfillment): bool => $fulfillment->status->holdsDriverWorkload(),
        );

        $orders = $active
            ->sortBy(fn (OrderFulfillment $fulfillment): int => (int) array_search($fulfillment->status, self::FIELD_QUEUE, true))
            ->concat($delivered->sortByDesc('updated_at'))
            ->map(fn (OrderFulfillment $fulfillment): array => $this->card($fulfillment))
            ->values()
            ->all();

        return Inertia::render('driver/orders/index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Finished drops (delivered, returned, cancelled) within a date range,
     * newest first, paged for infinite scroll. Defaults to the running month.
     */
    public function history(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $today = CarbonImmutable::today();
        $from = isset($validated['from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $validated['from'])
            : $today->startOfMonth();
        $to = isset($validated['to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $validated['to'])
            : $today;

        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        $historyStatuses = array_map(
            fn (FulfillmentStatus $status): string => $status->value,
            self::HISTORY_STATUSES,
        );

        $orders = OrderFulfillment::query()
            ->visibleInDriverHistory((int) $request->user()->id, $historyStatuses)
            ->whereBetween('updated_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'order',
                fn (Builder $order) => $order->where(fn (Builder $match) => $match
                    ->whereLike('order_number', '%'.$search.'%')
                    ->orWhereLike('shipping_address_snapshot->name', '%'.$search.'%')
                    ->orWhereLike('shipping_address_snapshot->address', '%'.$search.'%')
                    ->orWhereLike('shipping_address_snapshot->city', '%'.$search.'%')
                    ->orWhereLike('shipping_address_snapshot->phone', '%'.$search.'%')),
            ))
            ->with(['order.items', 'order.customer:id,name'])
            ->latest('updated_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (OrderFulfillment $fulfillment): array => $this->card($fulfillment));

        return Inertia::render('driver/history', [
            'orders' => Inertia::scroll($orders),
            'total' => $orders->total(),
            'filters' => [
                'search' => $search,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'today' => $today->format('Y-m-d'),
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
            'channel' => $conversation?->channelName(),
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
        Toast::success('Order picked up.');

        return back();
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->startDelivery($this->owned($request, $order), $request->user());
        Toast::success('Delivery started. Chat with the customer is open.');

        return back();
    }

    public function arrive(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->arrive($this->owned($request, $order), $request->user());
        Toast::success('Marked as arrived.');

        return back();
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,}$/'],
        ]);

        $this->deliveries->complete($this->owned($request, $order), $request->user(), $request->string('otp')->toString());
        Toast::success('Order marked as delivered.');

        return to_route('driver.orders.index');
    }

    public function resendDeliveryCode(Request $request, Order $order): RedirectResponse
    {
        $fulfillment = $this->owned($request, $order);

        if ($fulfillment->status !== FulfillmentStatus::Arrived) {
            throw ValidationException::withMessages([
                'code' => 'Send a new code only after you mark the order arrived.',
            ]);
        }

        $this->placements->resend($order);
        Toast::success('A new code was sent to the customer.');

        return back();
    }

    public function fail(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::enum(DeliveryFailureReason::class)],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->deliveries->fail(
            $this->owned($request, $order),
            $request->user(),
            DeliveryFailureReason::from($data['reason']),
            $data['note'],
        );
        Toast::success('Order marked as not delivered.');

        return back();
    }

    public function beginReturn(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->beginReturn($this->owned($request, $order), $request->user());
        Toast::success('Goods marked as returning to the branch.');

        return back();
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
        Toast::success('Return request sent.');

        return back();
    }

    private function owned(Request $request, Order $order): OrderFulfillment
    {
        $fulfillment = $order->fulfillment;
        $driverId = (int) $request->user()->id;
        $assigned = $fulfillment !== null && (int) $fulfillment->driver_id === $driverId;
        $ownSale = $order->source === OrderSource::DriverSale && (int) $order->placed_by === $driverId;
        $finishedHistory = $fulfillment !== null
            && $fulfillment->driver_id === null
            && in_array($fulfillment->status, self::HISTORY_STATUSES, true)
            && $fulfillment->wasLastAssignedDriver($driverId);

        abort_unless($fulfillment !== null && ($assigned || $ownSale || $finishedHistory), 404);

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
            'status' => $fulfillment->status->value,
            'statusLabel' => $fulfillment->status->label(),
            'canChat' => $fulfillment->status->allowsOrderChat(),
            'nextAction' => $fulfillment->status->driverNextAction(),
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
            'completedLabel' => in_array($fulfillment->status, self::HISTORY_STATUSES, true)
                ? $this->dayLabel($fulfillment->updated_at)
                : null,
        ];
    }

    private function dayLabel(?CarbonInterface $date): ?string
    {
        return match (true) {
            $date === null => null,
            $date->isToday() => 'Today',
            $date->isYesterday() => 'Yesterday',
            default => $date->format('D, d M Y'),
        };
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
            'demoOtpHint' => '',
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
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'discountLabel' => (float) $order->discount > 0
                ? '$'.number_format((float) $order->discount, 2)
                : null,
            'couponCode' => $order->coupon_code,
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
        ];
    }

    private function thread(Order $order): Conversation
    {
        $conversation = $order->conversation;

        abort_if($conversation === null, 404);

        return $conversation;
    }
}
