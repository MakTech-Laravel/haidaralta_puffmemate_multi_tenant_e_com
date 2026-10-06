<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Services\Order\StockTransferService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(private StockTransferService $transfers) {}

    public function index(): Response
    {
        $transfers = StockTransfer::query()
            ->with(['items.product:id,name', 'sourceBranch:id,name', 'destinationBranch:id,name', 'order:id,order_number'])
            ->latest('id')
            ->limit(100)
            ->get();

        return Inertia::render('platform/stock-transfers/index', [
            'transfers' => $transfers->map(fn (StockTransfer $transfer): array => $this->payload($transfer))->all(),
        ]);
    }

    public function approve(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->transfers->approve($stockTransfer, $request->user());
        Toast::success('Stock transfer approved.');

        return back();
    }

    public function pick(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->transfers->pick($stockTransfer, $request->user());
        Toast::success('Stock transfer picking started.');

        return back();
    }

    public function dispatch(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->transfers->dispatch($stockTransfer, $request->user());
        Toast::success('Stock transfer dispatched.');

        return back();
    }

    public function receive(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->transfers->receive($stockTransfer, $request->user());
        Toast::success('Stock transfer received.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StockTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'orderNumber' => $transfer->order?->order_number,
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
            'canReceive' => $transfer->status->destinationAction() === 'receive',
        ];
    }
}
