<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\FulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\MediaUrl;
use App\Support\Order\OrderTrackingLink;
use App\Support\OrderItemPackageDisplay;
use App\Support\OrderStatusTimeline;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only order page behind a signed link (guest checkout). It never shows
 * the delivery code, driver, branch, or any order action.
 */
class OrderTrackingController extends Controller
{
    public function show(Order $order): Response
    {
        abort_if(OrderTrackingLink::isExpired($order), 410, 'This order link has expired.');

        $order->load([
            'items',
            'statusHistories' => fn ($query) => $query->with('changer:id,name')->orderBy('id'),
        ]);

        return Inertia::render('storefront/order-tracking', [
            'order' => $this->detail($order),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Order $order): array
    {
        $snapshot = $order->shipping_address_snapshot ?? [];

        return [
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'statusLabel' => $order->status->customerLabel(),
            'paymentStatus' => $order->payment_status->value,
            'paymentStatusLabel' => $order->payment_status->customerLabel(),
            'date' => $order->created_at->format('d M Y'),
            'deliveryDate' => $order->delivery_date?->format('d M Y'),
            'timeSlot' => $order->time_slot,
            'products' => $order->items->map(function ($item): array {
                $itemSnapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];

                return [
                    'name' => $item->product_name_snapshot,
                    'image' => MediaUrl::resolve($itemSnapshot['image'] ?? null),
                    'packageLabel' => OrderItemPackageDisplay::subtitle($item),
                    'quantityLabel' => 'Quantity x '.$item->quantity,
                    'priceLabel' => '$'.number_format((float) $item->subtotal, 2),
                    'isLateDelivery' => (bool) $item->is_late_delivery,
                    'isRemoved' => $item->removed_at !== null,
                ];
            })->all(),
            'subtotalLabel' => '$'.number_format((float) $order->subtotal, 2),
            'discountLabel' => (float) $order->discount > 0
                ? '$'.number_format((float) $order->discount, 2)
                : null,
            'couponCode' => $order->coupon_code,
            'deliveryFeeLabel' => '$'.number_format((float) $order->delivery_fee, 2),
            'totalLabel' => '$'.number_format((float) $order->total_amount, 2),
            'shippingAddress' => [
                'name' => $snapshot['name'] ?? '',
                'lines' => array_values(array_filter([
                    $snapshot['address'] ?? null,
                    trim(($snapshot['city'] ?? '').' '.($snapshot['postal_code'] ?? '')),
                ])),
                'mobile' => $this->maskedMobile((string) ($snapshot['phone'] ?? '')),
            ],
            'timeline' => OrderStatusTimeline::steps(FulfillmentStatus::from($order->status->value), $order->statusHistories, true),
            'timelinePrevious' => OrderStatusTimeline::previousAttemptRails($order->statusHistories, true),
        ];
    }

    /**
     * Only the last three digits stay visible, e.g. "+61410123456" -> "*********456".
     */
    private function maskedMobile(string $phone): string
    {
        $phone = trim($phone);

        if (strlen($phone) <= 3) {
            return $phone;
        }

        return str_repeat('*', strlen($phone) - 3).substr($phone, -3);
    }
}
