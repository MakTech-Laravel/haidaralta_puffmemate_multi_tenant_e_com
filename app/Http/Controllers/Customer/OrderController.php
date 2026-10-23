<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderReturnType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use App\Models\WholesaleInquiry;
use App\Services\Conversation\ConversationService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Order\OrderReturnService;
use App\Support\ConversationPayload;
use App\Support\MediaUrl;
use App\Support\OrderStatusTimeline;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private FulfillmentStatusService $statuses,
        private OrderPlacementNotifier $placements,
        private OrderReturnService $returns,
        private ConversationService $conversations,
    ) {}

    /**
     * Display the customer's own order list.
     *
     * Never loads or serializes fulfillments/branch data — customers only
     * ever see the master order.
     */
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with(['items', 'fulfillment', 'conversation'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('customer/orders/index', [
            'orders' => $orders->getCollection()->map(fn (Order $order): array => $this->summarize($order))->all(),
            'meta' => [
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Display a single order's real tracking detail.
     */
    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

        $order->load([
            'items',
            'statusHistories' => fn ($query) => $query->with('changer:id,name')->orderBy('id'),
        ]);

        return Inertia::render('customer/orders/show', [
            'order' => $this->detail($order),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 404);
        abort_if($order->fulfillment === null, 404);

        $this->statuses->cancel($order->fulfillment, $request->user());

        return back();
    }

    public function resendPlacementCode(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

        $this->placements->resend($order);
        Toast::success('Delivery code sent.');

        return back();
    }

    public function storeReturn(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

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

    /**
     * Order-scoped chat with the driver, available once the order is on the way.
     */
    public function chat(Request $request, Order $order): Response
    {
        $conversation = $this->chatConversation($request, $order);
        $customer = $request->user();

        $this->conversations->markRead($conversation, $customer);

        return Inertia::render('customer/orders/chat', [
            'order' => [
                'orderNumber' => $order->order_number,
                'status' => $order->status->value,
                'statusLabel' => $order->status->customerLabel(),
                'driverName' => $order->fulfillment?->driver?->name,
            ],
            'messages' => ConversationPayload::messages(
                $conversation->messages()->with(['sender:id,name', 'sender.media', 'attachments'])->orderBy('id')->get(),
            ),
            'viewerId' => (int) $customer->id,
            'canSend' => $this->canSendChat($customer, $order, $conversation),
            'channel' => $conversation->channelName(),
        ]);
    }

    public function storeMessage(Request $request, Order $order): RedirectResponse
    {
        $conversation = $this->chatConversation($request, $order);
        abort_unless($this->canSendChat($request->user(), $order, $conversation), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->conversations->sendMessage($conversation, $request->user(), $validated['body']);

        return back();
    }

    /**
     * The customer's own order thread, or 404 while chat is unavailable.
     */
    private function chatConversation(Request $request, Order $order): Conversation
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

        $conversation = $order->conversation;

        abort_if(
            $conversation === null
                || ! $order->fulfillment?->status->allowsOrderChat()
                || Gate::forUser($request->user())->denies('view', $conversation),
            404,
        );

        return $conversation;
    }

    private function canChat(Order $order): bool
    {
        return $order->conversation !== null
            && $order->fulfillment?->status->allowsOrderChat() === true;
    }

    private function canSendChat(User $customer, Order $order, Conversation $conversation): bool
    {
        return Gate::forUser($customer)->allows('send', $conversation)
            && ! $order->fulfillment->status->isTerminal();
    }

    /**
     * @return array{orderNumber: string, products: list<string>, priceLabel: string, status: string, statusLabel: string, isActive: bool, canChat: bool, paymentStatus: string, source: string, sourceLabel: string, date: string}
     */
    private function summarize(Order $order): array
    {
        return [
            'orderNumber' => $order->order_number,
            'products' => $order->items->map(fn ($item): string => $item->product_name_snapshot.' (x'.$item->quantity.')')->all(),
            'priceLabel' => '$'.number_format((float) $order->total_amount, 2),
            'status' => $order->status->value,
            'statusLabel' => $order->status->customerLabel(),
            'isActive' => ! $order->status->isTerminal(),
            'canChat' => $this->canChat($order),
            'paymentStatus' => $order->payment_status->value,
            'source' => $order->source->value,
            'sourceLabel' => $order->source->label(),
            'date' => $order->created_at->format('d M Y'),
        ];
    }

    private function wholesaleRequestId(Order $order): ?int
    {
        if ($order->source !== OrderSource::Wholesale) {
            return null;
        }

        $id = WholesaleInquiry::query()
            ->where('order_id', $order->id)
            ->where('customer_id', $order->customer_id)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Order $order): array
    {
        return [
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'statusLabel' => $order->status->customerLabel(),
            'paymentStatus' => $order->payment_status->value,
            'paymentStatusLabel' => $order->payment_status->customerLabel(),
            'source' => $order->source->value,
            'sourceLabel' => $order->source->label(),
            'wholesaleRequestId' => $this->wholesaleRequestId($order),
            'deliveryCode' => $order->status->allowsPlacementCode()
                ? $this->placements->currentCode($order)
                : null,
            'canResendCode' => $order->status->allowsPlacementCode(),
            'canCancel' => $order->status->allowsCustomerCancel(),
            'canChat' => $this->canChat($order),
            'canRequestReturn' => in_array($order->status, [OrderStatus::Arrived, OrderStatus::Delivered], true)
                && $order->returns()->where('status', 'requested')->doesntExist(),
            'returnRequest' => $this->returns->payload($order),
            'date' => $order->created_at->format('d M Y'),
            'products' => $order->items->map(function ($item): array {
                $snapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];

                return [
                    'name' => $item->product_name_snapshot,
                    'image' => MediaUrl::resolve($snapshot['image'] ?? null),
                    'quantityLabel' => 'Quantity x '.$item->quantity,
                    'priceLabel' => '$'.number_format((float) $item->subtotal, 2),
                ];
            })->all(),
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'discountLabel' => (float) $order->discount > 0
                ? '$'.number_format((float) $order->discount, 2)
                : null,
            'couponCode' => $order->coupon_code,
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'shippingAddress' => [
                'name' => $order->shipping_address_snapshot['name'] ?? '',
                'lines' => array_values(array_filter([
                    $order->shipping_address_snapshot['address'] ?? null,
                    trim(($order->shipping_address_snapshot['city'] ?? '').' '.($order->shipping_address_snapshot['postal_code'] ?? '')),
                ])),
                'mobile' => $order->shipping_address_snapshot['phone'] ?? '',
            ],
            'timeline' => OrderStatusTimeline::steps(FulfillmentStatus::from($order->status->value), $order->statusHistories, true),
            'timelinePrevious' => OrderStatusTimeline::previousAttemptRails($order->statusHistories, true),
        ];
    }
}
