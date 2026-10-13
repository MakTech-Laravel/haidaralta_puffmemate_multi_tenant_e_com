<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\WholesaleInquiry;
use App\Models\WholesaleInquiryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WholesaleRequestController extends Controller
{
    /**
     * The signed-in customer's own wholesale requests.
     */
    public function index(Request $request): Response
    {
        $requests = WholesaleInquiry::query()
            ->where('customer_id', $request->user()->id)
            ->withCount('items')
            ->with('order:id,order_number')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('customer/wholesale-requests/index', [
            'requests' => $requests->getCollection()
                ->map(fn (WholesaleInquiry $inquiry): array => $this->summarize($inquiry))
                ->all(),
            'meta' => [
                'currentPage' => $requests->currentPage(),
                'lastPage' => $requests->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, WholesaleInquiry $wholesaleInquiry): Response
    {
        abort_unless($wholesaleInquiry->customer_id === $request->user()->id, 404);

        $wholesaleInquiry->load(['items', 'order:id,order_number']);

        return Inertia::render('customer/wholesale-requests/show', [
            'request' => [
                ...$this->summarize($wholesaleInquiry),
                'company' => $wholesaleInquiry->company,
                'phone' => $wholesaleInquiry->phone,
                'email' => $wholesaleInquiry->email,
                'address' => $wholesaleInquiry->address,
                'message' => $wholesaleInquiry->message,
                'rejectionReason' => $wholesaleInquiry->rejection_reason,
                'items' => $wholesaleInquiry->items
                    ->map(fn (WholesaleInquiryItem $item): array => [
                        'name' => $item->product_name_snapshot,
                        'sku' => $item->sku_snapshot,
                        'quantity' => $item->quantity,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array{id: int, status: string, statusLabel: string, itemCount: int, orderNumber: string|null, date: string}
     */
    private function summarize(WholesaleInquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'status' => $inquiry->status->value,
            'statusLabel' => $inquiry->status->customerLabel(),
            'itemCount' => (int) ($inquiry->items_count ?? $inquiry->items->count()),
            'orderNumber' => $inquiry->order?->order_number,
            'date' => $inquiry->created_at->format('d M Y'),
        ];
    }
}
