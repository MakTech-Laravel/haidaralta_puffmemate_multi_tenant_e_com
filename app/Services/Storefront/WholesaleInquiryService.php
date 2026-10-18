<?php

namespace App\Services\Storefront;

use App\Enums\NotificationType;
use App\Enums\PermissionEnum;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WholesaleInquiry;
use App\Services\Notifications\PermissionRecipientResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WholesaleInquiryService
{
    public function __construct(private PermissionRecipientResolver $recipients) {}

    /**
     * @param  array{
     *     full_name: string,
     *     phone: string,
     *     company?: string|null,
     *     email?: string|null,
     *     address: string,
     *     latitude: string|numeric,
     *     longitude: string|numeric,
     *     message?: string|null,
     *     items?: list<array{product_id: int|string, variant_id?: int|string|null, quantity: int|string}>|null
     * }  $validated
     */
    public function store(
        array $validated,
        ?string $ipAddress,
        ?string $userAgent,
        ?User $customer = null,
    ): WholesaleInquiry {
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $inquiry = DB::transaction(function () use ($validated, $items, $ipAddress, $userAgent, $customer): WholesaleInquiry {
            $inquiry = WholesaleInquiry::query()->create([
                ...$validated,
                'customer_id' => $customer?->isCustomer() ? $customer->id : null,
                'status' => WholesaleInquiryStatus::New,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            $this->storeItems($inquiry, $items);

            return $inquiry;
        });

        $this->notifyReviewers($inquiry);

        return $inquiry;
    }

    public function markRead(WholesaleInquiry $inquiry): void
    {
        if ($inquiry->status !== WholesaleInquiryStatus::New) {
            return;
        }

        $inquiry->forceFill([
            'status' => WholesaleInquiryStatus::Read,
            'read_at' => now(),
        ])->save();
    }

    public function reject(WholesaleInquiry $inquiry, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($inquiry, $reviewer, $reason): void {
            $locked = WholesaleInquiry::query()->whereKey($inquiry->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw ValidationException::withMessages([
                    'reason' => 'This inquiry is already closed.',
                ]);
            }

            $locked->forceFill([
                'status' => WholesaleInquiryStatus::Rejected,
                'rejection_reason' => trim($reason),
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'read_at' => $locked->read_at ?? now(),
            ])->save();
        });

        $inquiry->refresh();

        if ($inquiry->customer_id !== null) {
            notify_users(
                [$inquiry->customer_id],
                NotificationType::Order,
                'Wholesale request declined',
                'Your wholesale request #'.$inquiry->id.' was declined: '.trim($reason),
                [
                    'wholesale_inquiry_id' => $inquiry->id,
                    'link_url' => route('customer.wholesale-requests.show', $inquiry),
                    'link_label' => 'View request',
                ],
                $reviewer,
            );
        }
    }

    public function updateNote(WholesaleInquiry $inquiry, ?string $note): void
    {
        $note = is_string($note) ? trim($note) : null;

        $inquiry->forceFill([
            'admin_note' => $note === '' ? null : $note,
        ])->save();
    }

    /**
     * @param  list<array{product_id: int|string, variant_id?: int|string|null, quantity: int|string}>  $items
     */
    private function storeItems(WholesaleInquiry $inquiry, array $items): void
    {
        if ($items === []) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', array_map(fn (array $item): int => (int) $item['product_id'], $items))
            ->get(['id', 'name', 'sku'])
            ->keyBy('id');
        $variants = ProductVariant::query()
            ->whereIn('id', array_filter(array_map(self::variantId(...), $items)))
            ->get(['id', 'product_id', 'name', 'sku'])
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            if ($product === null) {
                continue;
            }

            $variantId = self::variantId($item);
            $variant = $variantId === null ? null : $variants->get($variantId);

            if ($variant !== null && $variant->product_id !== $product->id) {
                $variant = null;
            }

            $inquiry->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'product_name_snapshot' => $variant === null ? $product->name : $product->name.' · '.$variant->name,
                'sku_snapshot' => $variant?->sku ?: $product->sku,
                'quantity' => (int) $item['quantity'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function variantId(array $item): ?int
    {
        $value = $item['variant_id'] ?? null;

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function notifyReviewers(WholesaleInquiry $inquiry): void
    {
        $itemCount = $inquiry->items()->count();
        $from = $inquiry->company
            ? $inquiry->full_name.' ('.$inquiry->company.')'
            : $inquiry->full_name;

        notify_users(
            $this->recipients->platform(PermissionEnum::MANAGE_WHOLESALE),
            NotificationType::Order,
            'New wholesale inquiry',
            $itemCount > 0
                ? $from.' requested '.$itemCount.' '.str('product')->plural($itemCount).'.'
                : $from.' sent a wholesale inquiry.',
            [
                'wholesale_inquiry_id' => $inquiry->id,
                'link_url' => route('platform.wholesale-inquiries.show', $inquiry),
                'link_label' => 'View inquiry',
            ],
        );
    }
}
