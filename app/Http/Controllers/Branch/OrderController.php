<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchOrderFulfillmentDataTable;
use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateFulfillmentStatusRequest;
use App\Models\Branch;
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
    public function show(string $branch, OrderFulfillment $fulfillment): Response
    {
        $branch = $this->currentBranch();
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);

        $fulfillment->load(['order.customer', 'items', 'statusHistories' => fn ($query) => $query->orderBy('created_at')]);

        return Inertia::render('branch/orders/show', [
            'branch' => $branch->toPortalContext(),
            'fulfillment' => $this->detail($fulfillment),
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
        $fulfillment = $this->resolveForBranch($fulfillment, $branch);

        $this->payments->markFulfillmentPaid($fulfillment);

        Toast::success('Fulfillment marked as paid.');

        return redirect()->route('branch.orders.show', [$branch, $fulfillment]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;

        return [
            'id' => $fulfillment->id,
            'fulfillmentNumber' => $fulfillment->fulfillment_number,
            'status' => $fulfillment->status->value,
            'statusOptions' => FulfillmentStatus::options(),
            'shippingCostLabel' => '$'.number_format((float) $fulfillment->shipping_cost, 2),
            'distanceKmLabel' => $fulfillment->distance_km !== null ? number_format((float) $fulfillment->distance_km, 2).' km' : '—',
            'isPaid' => $fulfillment->isPaid(),
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'customerName' => $order->customer?->name ?? 'Customer',
                'paymentMethod' => $order->payment_method,
                'paymentStatus' => $order->payment_status->value,
                'shippingAddress' => $order->shipping_address_snapshot,
            ],
            'items' => $fulfillment->items->map(fn ($item): array => [
                'name' => $item->product_name_snapshot,
                'image' => MediaUrl::resolve($item->product_snapshot['image'] ?? null),
                'quantity' => $item->quantity,
                'unitPriceLabel' => '$'.number_format((float) $item->unit_price, 2),
                'subtotalLabel' => '$'.number_format((float) $item->subtotal, 2),
            ])->all(),
            'timeline' => $fulfillment->statusHistories->map(fn ($row): array => [
                'label' => FulfillmentStatus::from($row->new_status)->label(),
                'detail' => $row->created_at->format('d M Y, h:i A'),
                'comment' => $row->comment,
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
