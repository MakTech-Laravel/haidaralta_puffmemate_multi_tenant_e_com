<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\OrderDataTable;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\MarkOrderPaymentRequest;
use App\Http\Requests\Order\UpdateFulfillmentStatusRequest;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Order\DriverAssignmentService;
use App\Services\Order\DriverDeliveryService;
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
        private DriverAssignmentService $drivers,
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
            'items',
            'fulfillments.branch',
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

    public function assignDriver(Request $request, Order $order): RedirectResponse
    {
        $fulfillment = $this->requireFulfillment($order);
        $driver = User::query()->findOrFail($request->validate([
            'driver_id' => ['required', 'integer'],
        ])['driver_id']);

        $this->drivers->assign($fulfillment, $driver, $request->user());

        Toast::success('Driver assigned.');

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
        $fulfillment = $order->fulfillment;

        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'paymentStatus' => $order->payment_status->value,
            'paymentStatusOptions' => PaymentStatus::options(),
            'customerName' => $order->customer?->name ?? 'Customer',
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
            'shippingAddress' => $order->shipping_address_snapshot,
            'drivers' => $fulfillment === null
                ? []
                : $this->drivers->candidates($fulfillment)->map(fn (User $driver): array => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                ])->all(),
            'fulfillments' => $order->fulfillments->map(fn (OrderFulfillment $row): array => [
                'id' => $row->id,
                'fulfillmentNumber' => $row->fulfillment_number,
                'branchName' => $row->branch->name,
                'status' => $row->status->value,
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
                'canAssign' => in_array($row->status, [FulfillmentStatus::Ready, FulfillmentStatus::Assigned], true),
                'canCancel' => $row->status->allowsStaffCancel(),
                'canRetry' => $row->status === FulfillmentStatus::DeliveryFailed,
                'canReschedule' => $row->status === FulfillmentStatus::DeliveryFailed,
                'canReturn' => $row->status === FulfillmentStatus::DeliveryFailed,
                'canConfirmReturn' => $row->status === FulfillmentStatus::Returning,
                'items' => $row->items->map(fn ($item): array => [
                    'name' => $item->product_name_snapshot,
                    'image' => MediaUrl::resolve($item->product_snapshot['image'] ?? null),
                    'quantity' => $item->quantity,
                    'subtotalLabel' => '$'.number_format((float) $item->subtotal, 2),
                ])->all(),
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
                'label' => $row->new_status,
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
