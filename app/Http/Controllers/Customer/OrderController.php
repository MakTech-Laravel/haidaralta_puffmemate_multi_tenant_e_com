<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display the customer's own order list.
     *
     * Never loads or serializes fulfillments/branch data — customers only
     * ever see the master order.
     */
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('customer/orders/index', [
            'orders' => $orders->getCollection()->map(fn (Order $order): array => $this->summarize($order))->all(),
            'meta' => [
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Display a single order's real tracking detail.
     */
    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->customer_id === $request->user()->id, 404);

        $order->load(['items', 'statusHistories' => fn ($query) => $query->orderBy('created_at')]);

        return Inertia::render('customer/orders/show', [
            'order' => $this->detail($order),
        ]);
    }

    /**
     * @return array{orderNumber: string, products: list<string>, priceLabel: string, status: string, paymentStatus: string, date: string}
     */
    private function summarize(Order $order): array
    {
        return [
            'orderNumber' => $order->order_number,
            'products' => $order->items->map(fn ($item): string => $item->product_name_snapshot.' (x'.$item->quantity.')')->all(),
            'priceLabel' => '$'.number_format((float) $order->total_amount, 2),
            'status' => $order->status->value,
            'paymentStatus' => $order->payment_status->value,
            'date' => $order->created_at->format('d M Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Order $order): array
    {
        return [
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'paymentStatus' => $order->payment_status->value,
            'date' => $order->created_at->format('d M Y'),
            'products' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name_snapshot,
                'quantityLabel' => $item->quantity === 1 ? '1 Unit' : $item->quantity.' Units',
                'priceLabel' => '$'.number_format((float) $item->subtotal, 2),
            ])->all(),
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'shippingAddress' => [
                'name' => $order->shipping_address_snapshot['name'] ?? '',
                'lines' => array_values(array_filter([
                    $order->shipping_address_snapshot['address'] ?? null,
                    trim(($order->shipping_address_snapshot['city'] ?? '').' '.($order->shipping_address_snapshot['postal_code'] ?? '')),
                ])),
                'mobile' => $order->shipping_address_snapshot['phone'] ?? '',
            ],
            'timeline' => $this->timeline($order),
        ];
    }

    /**
     * @return list<array{key: string, label: string, state: string, detail: string}>
     */
    private function timeline(Order $order): array
    {
        /** @var Collection<int, OrderStatusHistory> $history */
        $history = $order->statusHistories;

        return $history
            ->map(fn ($row): array => [
                'key' => (string) $row->id,
                'label' => OrderStatus::from($row->new_status)->label(),
                'state' => 'completed',
                'detail' => $row->created_at->format('d M Y, h:i A'),
            ])
            ->push([
                'key' => 'current',
                'label' => $order->status->label(),
                'state' => $order->status->isTerminal() ? 'completed' : 'current',
                'detail' => $order->status->isTerminal() ? 'Completed' : 'In progress',
            ])
            ->unique('label')
            ->values()
            ->all();
    }
}
