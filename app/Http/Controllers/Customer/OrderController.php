<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderReturnType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Order\OrderReturnService;
use App\Support\MediaUrl;
use App\Support\OrderStatusTimeline;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private FulfillmentStatusService $statuses,
        private OrderPlacementNotifier $placements,
        private OrderReturnService $returns,
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
            ->with('items')
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

        $this->placements->issue($order);
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
     * @return array{orderNumber: string, products: list<string>, priceLabel: string, status: string, statusLabel: string, isActive: bool, paymentStatus: string, date: string}
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
            'paymentStatus' => $order->payment_status->value,
            'date' => $order->created_at->format('d M Y'),
        ];
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
            'handoverNotice' => $order->status->allowsPlacementCode()
                ? 'A delivery code was sent to your phone, email, and notifications. Give it to the driver only after you have paid.'
                : null,
            'canResendCode' => $order->status->allowsPlacementCode(),
            'canCancel' => $order->status->allowsCustomerCancel(),
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
        ];
    }
}
