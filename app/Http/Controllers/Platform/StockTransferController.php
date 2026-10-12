<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\StockTransferDataTable;
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

    public function index(Request $request): Response
    {
        return Inertia::render('platform/stock-transfers/index', [
            'table' => StockTransferDataTable::make($request),
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
}
