<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchOrderFulfillmentDataTable;
use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\HandoverVerification;
use App\Enums\PermissionEnum;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateFulfillmentStatusRequest;
use App\Models\Branch;
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
     * List fulfillments assigned to the current branch only.
     */
    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/orders/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchOrderFulfillmentDataTable::makeForBranch($request, $branch),
        ]);
    }

    /**
     * Show a single fulfillment's detail, scoped to the current branch.
     */
    public function show(Request $request, string $branch, OrderFulfillment $fulfillment): Response
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);

        $fulfillment->load([
            'order.customer',
            'items',
            'stockTransfers.items.product:id,name',
            'stockTransfers.sourceBranch:id,name',
            'stockTransfers.destinationBranch:id,name',
            'statusHistories' => fn ($query) => $query->with('changer:id,name')->orderBy('id'),
        ]);

        return Inertia::render('branch/orders/show', [
            'branch' => $branch->toPortalContext(),
            'fulfillment' => $this->detail(
                $fulfillment,
                $request->user('branch')?->can(PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE->value) ?? false,
            ),
        ]);
    }

    /**
     * Update the fulfillment's status (Pending → Processing → Ready → Delivered, or Cancelled).
     */
    public function updateStatus(UpdateFulfillmentStatusRequest $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);

        $status = FulfillmentStatus::from($request->validated('status'));

        $this->statusService->transition($fulfillment, $status, $request->user());

        Toast::success('Fulfillment status updated.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    /**
     * Mark this fulfillment's cash/POS collection as paid.
     *
     * Phase 1 only — driver workflow will later move this responsibility to
     * the assigned driver.
     */
    public function markPaid(string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->resolveForBranch($fulfillment, $branch);

        throw ValidationException::withMessages([
            'payment_status' => 'Payment is collected when the delivery code is confirmed.',
        ]);
    }

    public function deliver(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $data = $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,}$/'],
        ]);

        $this->deliveries->complete($fulfillment, $request->user(), $data['otp']);
        Toast::success('Order delivered and payment collected.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function deliverWithoutCode(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $data = $request->validate([
            'method' => ['required', Rule::enum(HandoverVerification::class)],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->deliveries->completeWithoutCode(
            $fulfillment,
            $request->user(),
            HandoverVerification::from($data['method']),
            $data['note'],
        );
        Toast::success('Order delivered without a code.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function fail(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $data = $request->validate([
            'reason' => ['required', Rule::enum(DeliveryFailureReason::class)],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->deliveries->fail(
            $fulfillment,
            $request->user(),
            DeliveryFailureReason::from($data['reason']),
            $data['note'],
        );
        Toast::success('Order marked as not delivered.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function approveReturn(Request $request, string $branch, OrderFulfillment $fulfillment, OrderReturn $orderReturn): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        abort_unless($orderReturn->order_id === $fulfillment->order_id, 404);
        $this->returns->approve($orderReturn, $request->user());
        Toast::success('Return request approved.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function rejectReturn(Request $request, string $branch, OrderFulfillment $fulfillment, OrderReturn $orderReturn): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        abort_unless($orderReturn->order_id === $fulfillment->order_id, 404);
        $this->returns->reject($orderReturn, $request->user());
        Toast::success('Return request rejected.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function resendPlacementCode(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $this->placements->resend($fulfillment->order);
        Toast::success('Delivery code sent.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function cancel(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);

        $this->statusService->cancel($fulfillment, $request->user());

        Toast::success('Order cancelled.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function retryDelivery(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $this->deliveries->retry($fulfillment, $request->user());
        Toast::success('Delivery retry started.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function rescheduleDelivery(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $this->deliveries->reschedule($fulfillment, $request->user());
        Toast::success('Delivery rescheduled.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function returnDelivery(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $this->deliveries->beginReturn($fulfillment, $request->user());
        Toast::success('Return started.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    public function confirmReturn(Request $request, string $branch, OrderFulfillment $fulfillment): RedirectResponse
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);
        $this->deliveries->confirmReturn($fulfillment, $request->user());
        Toast::success('Return confirmed.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(OrderFulfillment $fulfillment, bool $canOverrideCode): array
    {
        $order = $fulfillment->order;

        return [
            'id' => $fulfillment->id,
            'fulfillmentNumber' => $fulfillment->fulfillment_number,
            'status' => $fulfillment->status->value,
            'actions' => $this->statusService->staffActions($fulfillment),
            'drivers' => $this->orderAssign->candidatesPayload($fulfillment),
            'canAssign' => $this->orderAssign->canAssign($fulfillment),
            'canResendCode' => $fulfillment->status->allowsPlacementCode(),
            'canCancel' => $fulfillment->status->allowsStaffCancel(),
            'canRetry' => false,
            'canReschedule' => false,
            'canReturn' => $fulfillment->status === FulfillmentStatus::DeliveryFailed,
            'canConfirmReturn' => $fulfillment->status === FulfillmentStatus::Returning,
            'canComplete' => $fulfillment->status === FulfillmentStatus::Arrived,
            'canReportUnavailable' => $fulfillment->status === FulfillmentStatus::Arrived,
            'canCompleteWithoutCode' => $canOverrideCode && $fulfillment->status === FulfillmentStatus::Arrived,
            'failureReasons' => array_map(
                fn (DeliveryFailureReason $reason): array => [
                    'value' => $reason->value,
                    'label' => $reason->label(),
                ],
                DeliveryFailureReason::cases(),
            ),
            'verificationMethods' => array_map(
                fn (HandoverVerification $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ],
                HandoverVerification::cases(),
            ),
            'driverId' => $fulfillment->driver_id,
            'shippingCostLabel' => '$'.number_format((float) $fulfillment->shipping_cost, 2),
            'distanceKmLabel' => $fulfillment->distance_km !== null ? number_format((float) $fulfillment->distance_km, 2).' km' : '—',
            'isPaid' => $fulfillment->isPaid(),
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'customerName' => $order->customer?->name ?? 'Customer',
                'paymentMethod' => $order->payment_method,
                'paymentStatus' => $order->payment_status->value,
                'returnRequest' => $order ? $this->returns->payload($order) : null,
                'paymentStatusLabel' => $order->payment_status->label(),
                'shippingAddress' => $order->shipping_address_snapshot,
            ],
            'items' => $fulfillment->items->map(fn ($item): array => [
                'name' => $item->product_name_snapshot,
                'image' => MediaUrl::resolve($item->product_snapshot['image'] ?? null),
                'quantity' => $item->quantity,
                'unitPriceLabel' => '$'.number_format((float) $item->unit_price, 2),
                'subtotalLabel' => '$'.number_format((float) $item->subtotal, 2),
            ])->all(),
            'timeline' => OrderStatusTimeline::steps($fulfillment->status, $fulfillment->statusHistories),
            'transfers' => $fulfillment->stockTransfers->map(fn (StockTransfer $transfer): array => [
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
                'canReceive' => $transfer->destination_branch_id === $fulfillment->branch_id
                    && $transfer->status === StockTransferStatus::InTransit,
            ])->all(),
        ];
    }

    private function resolveForBranch(OrderFulfillment $fulfillment, Branch $branch): OrderFulfillment
    {
        abort_unless($fulfillment->branch_id === $branch->id, 404);

        return $fulfillment;
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
