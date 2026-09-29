<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
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
        $branch = $this->currentBranch();

        $transfers = StockTransfer::query()
            ->where(function ($query) use ($branch): void {
                $query->where('source_branch_id', $branch->id)
                    ->orWhere('destination_branch_id', $branch->id);
            })
            ->with(['items.product:id,name', 'sourceBranch:id,name', 'destinationBranch:id,name', 'order:id,order_number'])
            ->latest('id')
            ->limit(100)
            ->get();

        return Inertia::render('branch/stock-transfers/index', [
            'branch' => $branch->toPortalContext(),
            'transfers' => $transfers->map(fn (StockTransfer $transfer): array => $this->payload($transfer, $branch))->all(),
        ]);
    }

    public function approve(Request $request, string $branch, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->assertSource($stockTransfer);
        $this->transfers->approve($stockTransfer, $request->user());
        Toast::success('Stock transfer approved.');

        return back();
    }

    public function pick(Request $request, string $branch, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->assertSource($stockTransfer);
        $this->transfers->pick($stockTransfer, $request->user());
        Toast::success('Stock transfer picking started.');

        return back();
    }

    public function dispatch(Request $request, string $branch, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->assertSource($stockTransfer);
        $this->transfers->dispatch($stockTransfer, $request->user());
        Toast::success('Stock transfer dispatched.');

        return back();
    }

    public function receive(Request $request, string $branch, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->assertDestination($stockTransfer);
        $this->transfers->receive($stockTransfer, $request->user());
        Toast::success('Stock transfer received.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StockTransfer $transfer, Branch $branch): array
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
            'canApprove' => $transfer->source_branch_id === $branch->id && $transfer->status->sourceAction() === 'approve',
            'canPick' => $transfer->source_branch_id === $branch->id && $transfer->status->sourceAction() === 'pick',
            'canDispatch' => $transfer->source_branch_id === $branch->id && $transfer->status->sourceAction() === 'dispatch',
            'canReceive' => $transfer->destination_branch_id === $branch->id && $transfer->status->destinationAction() === 'receive',
        ];
    }

    private function assertSource(StockTransfer $transfer): void
    {
        abort_unless($transfer->source_branch_id === $this->currentBranch()->id, 404);
    }

    private function assertDestination(StockTransfer $transfer): void
    {
        abort_unless($transfer->destination_branch_id === $this->currentBranch()->id, 404);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
