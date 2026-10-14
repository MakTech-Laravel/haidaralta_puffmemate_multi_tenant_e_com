<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\OrderDataTable;
use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\MarkOrderPaymentRequest;
use App\Http\Requests\Order\UpdateFulfillmentStatusRequest;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderReturn;
use App\Models\StockTransfer;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderAssignService;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Order\OrderReturnService;
use App\Services\Order\PaymentMarkingService;
use App\Support\MediaUrl;
use App\Support\OrderStatusTimeline;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private FulfillmentStatusService $statusService,
        private PaymentMarkingService $payments,
        private OrderAssignService $orderAssign,
        private DriverDeliveryService $deliveries,
        private OrderPlacementNotifier $placements,
        private OrderReturnService $returns,
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
            'statusHistories' => fn ($query) => $query->with('changer:id,name')->orderBy('id'),
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

    public function resendPlacementCode(Request $request, Order $order): RedirectResponse
    {
        $this->placements->issue($order);
        Toast::success('Delivery code sent.');

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
        throw ValidationException::withMessages([
            'payment_status' => 'Payment is collected when the delivery code is confirmed.',
        ]);
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,}$/'],
        ]);

        $this->deliveries->complete($this->requireFulfillment($order), $request->user(), $data['otp']);
        Toast::success('Order delivered and payment collected.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function fail(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::enum(DeliveryFailureReason::class)],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->deliveries->fail(
            $this->requireFulfillment($order),
            $request->user(),
            DeliveryFailureReason::from($data['reason']),
            $data['note'],
        );
        Toast::success('Order marked as not delivered.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function approveReturn(Request $request, Order $order, OrderReturn $orderReturn): RedirectResponse
    {
        abort_unless($orderReturn->order_id === $order->id, 404);
        $this->returns->approve($orderReturn, $request->user());
        Toast::success('Return request approved.');

        return redirect()->route('platform.orders.show', $order);
    }

    public function rejectReturn(Request $request, Order $order, OrderReturn $orderReturn): RedirectResponse
    {
        abort_unless($orderReturn->order_id === $order->id, 404);
        $this->returns->reject($orderReturn, $request->user());
        Toast::success('Return request rejected.');

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
            'canResendCode' => $order->status->allowsPlacementCode(),
            'returnRequest' => $this->returns->payload($order),
            'canComplete' => $order->fulfillment?->status === FulfillmentStatus::Arrived,
            'canReportUnavailable' => $order->fulfillment?->status === FulfillmentStatus::Arrived,
            'failureReasons' => array_map(
                fn (DeliveryFailureReason $reason): array => [
                    'value' => $reason->value,
                    'label' => $reason->label(),
                ],
                DeliveryFailureReason::cases(),
            ),
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
                'actions' => $this->statusService->staffActions($row),
                'shippingCostLabel' => '$'.number_format((float) $row->shipping_cost, 2),
                'isPaid' => $row->isPaid(),
                'driverId' => $row->driver_id,
                'driverName' => $row->driver?->name,
                'canAssign' => $this->orderAssign->canAssign($row),
                'canCancel' => $row->status->allowsStaffCancel(),
                'canRetry' => false,
                'canReschedule' => false,
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
            'timeline' => OrderStatusTimeline::steps(FulfillmentStatus::from($order->status->value), $order->statusHistories),
        ];
    }

    private function requireFulfillment(Order $order): OrderFulfillment
    {
        abort_if($order->fulfillment === null, 404);

        return $order->fulfillment;
    }
}
