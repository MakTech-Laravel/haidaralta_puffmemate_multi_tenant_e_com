<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\StockTransferDataTable;
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

    public function index(Request $request): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/stock-transfers/index', [
            'branch' => $branch->toPortalContext(),
            'table' => StockTransferDataTable::makeForBranch($request, $branch),
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
