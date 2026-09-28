<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\OrderDataTable;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\MarkOrderPaymentRequest;
use App\Http\Requests\Order\UpdateFulfillmentStatusRequest;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\PaymentMarkingService;
use App\Support\MediaUrl;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private FulfillmentStatusService $statusService,
        private PaymentMarkingService $payments,
    ) {}

    /**
     * List every order across every branch.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('platform/orders/index', [
            'table' => OrderDataTable::make($request),
        ]);
    }

    /**
     * Show an order with every one of its fulfillments visible.
     */
    public function show(Order $order): Response
    {
        $order->load([
            'customer',
            'items',
            'fulfillments.branch',
            'fulfillments.items',
            'statusHistories' => fn ($query) => $query->orderBy('created_at'),
        ]);

        return Inertia::render('platform/orders/show', [
            'order' => $this->detail($order),
        ]);
    }

    /**
     * Update any fulfillment's status, regardless of branch.
     */
    public function updateStatus(UpdateFulfillmentStatusRequest $request, Order $order, OrderFulfillment $fulfillment): RedirectResponse
    {
        abort_unless($fulfillment->order_id === $order->id, 404);

        $status = FulfillmentStatus::from($request->validated('status'));

        $this->statusService->transition($fulfillment, $status, $request->user());

        Toast::success('Fulfillment status updated.');

        return redirect()->route('platform.orders.show', $order);
    }

    /**
     * Platform override of the master order's payment_status.
     */
    public function markPaid(MarkOrderPaymentRequest $request, Order $order): RedirectResponse
    {
        $status = PaymentStatus::from($request->validated('payment_status'));

        $this->payments->overrideOrderPaymentStatus($order, $status);

        Toast::success('Order payment status updated.');

        return redirect()->route('platform.orders.show', $order);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'paymentStatus' => $order->payment_status->value,
            'paymentStatusOptions' => array_map(
                fn (PaymentStatus $status): array => ['value' => $status->value, 'label' => ucfirst($status->value)],
                PaymentStatus::cases(),
            ),
            'customerName' => $order->customer?->name ?? 'Customer',
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
            'shippingAddress' => $order->shipping_address_snapshot,
            'fulfillments' => $order->fulfillments->map(fn (OrderFulfillment $fulfillment): array => [
                'id' => $fulfillment->id,
                'fulfillmentNumber' => $fulfillment->fulfillment_number,
                'branchName' => $fulfillment->branch->name,
                'status' => $fulfillment->status->value,
                'statusOptions' => FulfillmentStatus::options(),
                'shippingCostLabel' => '$'.number_format((float) $fulfillment->shipping_cost, 2),
                'isPaid' => $fulfillment->isPaid(),
                'items' => $fulfillment->items->map(fn ($item): array => [
                    'name' => $item->product_name_snapshot,
                    'image' => MediaUrl::resolve($item->product_snapshot['image'] ?? null),
                    'quantity' => $item->quantity,
                    'subtotalLabel' => '$'.number_format((float) $item->subtotal, 2),
                ])->all(),
            ])->all(),
            'timeline' => $order->statusHistories->map(fn ($row): array => [
                'label' => $row->new_status,
                'detail' => $row->created_at->format('d M Y, h:i A'),
                'comment' => $row->comment,
            ])->all(),
        ];
    }
}
