<?php

namespace App\Services\Customer;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductToReviewService
{
    /**
     * @return LengthAwarePaginator<int, array{id: string, name: string, image: string, orderId: string, shippingStatus: string, reviewed: bool, viewUrl: string|null}>
     */
    public function paginateForCustomer(User $customer): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, OrderItem> $page */
        $page = $this->deliveredItemsFor($customer)
            ->paginate(12)
            ->withQueryString();

        $reviewedKeys = $this->reviewedKeysFor($page->getCollection());

        $page->setCollection(
            $page->getCollection()->map(
                fn (OrderItem $item): array => $this->toPayload($item, isset($reviewedKeys[$this->reviewKey($item)])),
            ),
        );

        return $page;
    }

    /**
     * @return array{id: string, name: string, image: string, orderId: string, shippingStatus: string, reviewed: bool, viewUrl: string|null}|null
     */
    public function findForCustomer(User $customer, int $itemId): ?array
    {
        $item = $this->findItemForCustomer($customer, $itemId);

        if ($item === null) {
            return null;
        }

        return $this->toPayload($item, $this->hasReview($item));
    }

    public function findItemForCustomer(User $customer, int $itemId): ?OrderItem
    {
        return $this->deliveredItemsFor($customer)
            ->where('order_items.id', $itemId)
            ->first();
    }

    public function hasReview(OrderItem $item): bool
    {
        return ProductReview::query()
            ->where('order_id', $item->order_id)
            ->where('product_id', $item->product_id)
            ->exists();
    }

    public function storeForCustomer(User $customer, int $itemId, int $rating, string $body): ProductReview
    {
        $item = $this->findItemForCustomer($customer, $itemId);

        if ($item === null) {
            throw new NotFoundHttpException;
        }

        try {
            return ProductReview::query()->create([
                'user_id' => $customer->id,
                'order_id' => $item->order_id,
                'product_id' => $item->product_id,
                'rating' => $rating,
                'body' => $body,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'body' => 'You have already reviewed this product for this order.',
            ]);
        }
    }

    public function productReviewsUrl(Product $product): string
    {
        return route('products.show', [
            'product' => $product->slug,
            'tab' => 'reviews',
        ], false);
    }

    /**
     * @return Builder<OrderItem>
     */
    private function deliveredItemsFor(User $customer): Builder
    {
        return OrderItem::query()
            ->select('order_items.*')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.customer_id', $customer->id)
            ->where('orders.status', OrderStatus::Delivered)
            ->with([
                'order' => fn ($query) => $query->select('id', 'order_number', 'status', 'customer_id', 'created_at'),
                'product' => fn ($query) => $query->select('id', 'slug'),
            ])
            ->orderByDesc('orders.created_at')
            ->orderByDesc('order_items.id');
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return array<string, true>
     */
    private function reviewedKeysFor(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        $keys = [];

        ProductReview::query()
            ->whereIn('order_id', $items->pluck('order_id')->unique()->all())
            ->whereIn('product_id', $items->pluck('product_id')->unique()->all())
            ->get(['order_id', 'product_id'])
            ->each(function (ProductReview $review) use (&$keys): void {
                $keys[$review->order_id.'-'.$review->product_id] = true;
            });

        return $keys;
    }

    private function reviewKey(OrderItem $item): string
    {
        return $item->order_id.'-'.$item->product_id;
    }

    /**
     * @return array{id: string, name: string, image: string, orderId: string, shippingStatus: string, reviewed: bool, viewUrl: string|null}
     */
    private function toPayload(OrderItem $item, bool $reviewed): array
    {
        $snapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];
        $product = $item->product;
        $viewUrl = $reviewed && $product !== null
            ? $this->productReviewsUrl($product)
            : null;

        return [
            'id' => (string) $item->id,
            'name' => $item->product_name_snapshot,
            'image' => (string) MediaUrl::resolve($snapshot['image'] ?? null),
            'orderId' => $item->order->order_number,
            'shippingStatus' => OrderStatus::Delivered->value,
            'reviewed' => $reviewed,
            'viewUrl' => $viewUrl,
        ];
    }
}
