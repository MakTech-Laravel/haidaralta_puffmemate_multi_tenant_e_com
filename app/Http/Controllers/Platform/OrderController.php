<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\OrderDataTable;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\MarkOrderPaymentRequest;
use App\Http\Requests\Order\UpdateFulfillmentStatusRequest;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\StockTransfer;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderAssignService;
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
        private OrderAssignService $orderAssign,
        private DriverDeliveryService $deliveries,
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
            'placedBy:id,name',
            'items',
            'fulfillments.branch',
            'fulfillments.driver:id,name',
            'fulfillments.items',
            'fulfillments.stockTransfers.items.product:id,name',
            'fulfillments.stockTransfers.sourceBranch:id,name',
            'fulfillments.stockTransfers.destinationBranch:id,name',
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

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->statusService->cancel($this->requireFulfillment($order), $request->user());

        Toast::success('Order cancelled.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function retryDelivery(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->retry($this->requireFulfillment($order), $request->user());
        Toast::success('Delivery retry started.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function rescheduleDelivery(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->reschedule($this->requireFulfillment($order), $request->user());
        Toast::success('Delivery rescheduled.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function returnDelivery(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->beginReturn($this->requireFulfillment($order), $request->user());
        Toast::success('Return started.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function confirmReturn(Request $request, Order $order): RedirectResponse
    {
        $this->deliveries->confirmReturn($this->requireFulfillment($order), $request->user());
        Toast::success('Return confirmed.');

        return redirect()->route('platform.orders.show', $order);
    }

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
        $paymentOptions = collect(PaymentStatus::actionableOptions());

        if ($paymentOptions->doesntContain(fn (array $option): bool => $option['value'] === $order->payment_status->value)) {
            $paymentOptions->prepend([
                'value' => $order->payment_status->value,
                'label' => $order->payment_status->label(),
            ]);
        }

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'paymentStatus' => $order->payment_status->value,
            'paymentStatusLabel' => $order->payment_status->label(),
            'source' => $order->source->value,
            'sourceLabel' => $order->source->label(),
            'placedByName' => $order->placedBy?->name,
            'paymentStatusOptions' => $paymentOptions->values()->all(),
            'paymentMethod' => $order->payment_method,
            'customerName' => $order->customer?->name ?? 'Customer',
            'placedAt' => $order->created_at?->format('d M Y, h:i A'),
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'discountLabel' => (float) $order->discount > 0
                ? '$'.number_format((float) $order->discount, 2)
                : null,
            'couponCode' => $order->coupon_code,
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
            'shippingAddress' => $order->shipping_address_snapshot,
            'drivers' => [],
            'fulfillments' => $order->fulfillments->map(fn (OrderFulfillment $row): array => [
                'id' => $row->id,
                'fulfillmentNumber' => $row->fulfillment_number,
                'branchName' => $row->branch->name,
                'status' => $row->status->value,
                'statusLabel' => $row->status->label(),
                'statusOptions' => collect([$row->status, ...$row->status->staffNext()])
                    ->unique(fn (FulfillmentStatus $status): string => $status->value)
                    ->map(fn (FulfillmentStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
                'shippingCostLabel' => '$'.number_format((float) $row->shipping_cost, 2),
                'isPaid' => $row->isPaid(),
                'driverId' => $row->driver_id,
                'driverName' => $row->driver?->name,
                'canAssign' => $this->orderAssign->canAssign($row),
                'canCancel' => $row->status->allowsStaffCancel(),
                'canRetry' => $row->status === FulfillmentStatus::DeliveryFailed,
                'canReschedule' => $row->status === FulfillmentStatus::DeliveryFailed,
                'canReturn' => $row->status === FulfillmentStatus::DeliveryFailed,
                'canConfirmReturn' => $row->status === FulfillmentStatus::Returning,
                'items' => $row->items->map(function ($item): array {
                    $snapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];

                    return [
                        'name' => $item->product_name_snapshot,
                        'image' => MediaUrl::resolve($snapshot['image'] ?? null),
                        'quantity' => $item->quantity,
                        'subtotalLabel' => '$'.number_format((float) $item->subtotal, 2),
                    ];
                })->all(),
                'transfers' => $row->stockTransfers->map(fn (StockTransfer $transfer): array => [
                    'id' => $transfer->id,
                    'status' => $transfer->status->value,
                    'statusLabel' => $transfer->status->label(),
                    'sourceName' => $transfer->sourceBranch?->name,
                    'destinationName' => $transfer->destinationBranch?->name,
                    'estimatedCostLabel' => '$'.number_format((float) $transfer->estimated_cost, 2),
                    'items' => $transfer->items->map(fn ($item): array => [
                        'name' => $item->product?->name ?? 'Product',
                        'quantity' => $item->quantity,
                    ])->all(),
                    'canApprove' => $transfer->status->sourceAction() === 'approve',
                    'canPick' => $transfer->status->sourceAction() === 'pick',
                    'canDispatch' => $transfer->status->sourceAction() === 'dispatch',
                    'canReceive' => $transfer->status === StockTransferStatus::InTransit,
                ])->all(),
            ])->all(),
            'timeline' => $order->statusHistories->map(fn ($row): array => [
                'label' => OrderStatus::tryFrom((string) $row->new_status)?->label()
                    ?? FulfillmentStatus::tryFrom((string) $row->new_status)?->label()
                    ?? (string) $row->new_status,
                'detail' => $row->created_at->format('d M Y, h:i A'),
                'comment' => $row->comment,
            ])->all(),
        ];
    }

    private function requireFulfillment(Order $order): OrderFulfillment
    {
        abort_if($order->fulfillment === null, 404);

        return $order->fulfillment;
    }
}
