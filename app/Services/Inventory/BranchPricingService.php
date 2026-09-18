<?php

namespace App\Services\Inventory;

use App\Enums\DiscountType;
use App\Enums\PriceChangeSource;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\BranchProductPriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class BranchPricingService
{
    /**
     * @var list<string>
     */
    private const TRACKED_FIELDS = [
        'buying_price',
        'regular_price',
        'selling_price',
        'discount_type',
        'discount_price',
        'start_date',
        'end_date',
    ];

    /**
     * @param  array{
     *     selling_price: float|string,
     *     buying_price?: float|string|null,
     *     regular_price?: float|string|null,
     *     discount_type?: string|DiscountType|null,
     *     discount_price?: float|string|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     variant_id?: int|null
     * }  $data
     */
    public function upsert(
        Branch $branch,
        Product $product,
        array $data,
        ?ProductVariant $variant = null,
        ?User $actor = null,
        PriceChangeSource $source = PriceChangeSource::PriceUpsert,
    ): BranchProductPrice {
        return DB::transaction(function () use ($branch, $product, $data, $variant, $actor, $source): BranchProductPrice {
            $variantId = $variant?->id ?? ($data['variant_id'] ?? null);
            $price = $this->findActive($branch, $product, $variantId) ?? new BranchProductPrice([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'variant_id' => $variantId,
            ]);

            $discountType = $this->resolveDiscountType($data['discount_type'] ?? null, $data['discount_price'] ?? null);
            $hasDiscount = $discountType !== DiscountType::None;

            $price->fill([
                'buying_price' => array_key_exists('buying_price', $data)
                    ? self::nullableMoney($data['buying_price'])
                    : $price->buying_price,
                'regular_price' => array_key_exists('regular_price', $data)
                    ? self::nullableMoney($data['regular_price'])
                    : $price->regular_price,
                'selling_price' => self::nullableMoney($data['selling_price']) ?? $data['selling_price'],
                'discount_type' => $discountType,
                'discount_price' => $hasDiscount ? self::nullableMoney($data['discount_price'] ?? null) : null,
                'start_date' => $hasDiscount ? ($data['start_date'] ?? null) : null,
                'end_date' => $hasDiscount ? ($data['end_date'] ?? null) : null,
                'status' => true,
            ]);

            $changes = $this->pendingChanges($price);
            $price->save();

            $this->recordHistory($price, $changes, $source, $actor);

            return $price->refresh();
        });
    }

    public function updateBuyingPrice(
        Branch $branch,
        Product $product,
        float|string $buyingPrice,
        ?ProductVariant $variant = null,
        ?User $actor = null,
    ): void {
        $price = $this->findActive($branch, $product, $variant?->id);

        if ($price === null) {
            return;
        }

        $normalized = self::nullableMoney($buyingPrice);

        if ($normalized === null || (string) $price->buying_price === $normalized) {
            return;
        }

        $price->buying_price = $normalized;
        $changes = $this->pendingChanges($price);
        $price->save();

        $this->recordHistory($price, $changes, PriceChangeSource::StockIn, $actor);
    }

    private function findActive(Branch $branch, Product $product, mixed $variantId): ?BranchProductPrice
    {
        $query = BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('status', true);

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        return $query->first();
    }

    /**
     * @return array<string, array{from: ?string, to: ?string}>
     */
    private function pendingChanges(BranchProductPrice $price): array
    {
        $changes = [];

        foreach (self::TRACKED_FIELDS as $field) {
            if (! $price->isDirty($field)) {
                continue;
            }

            $from = $this->historyValue($field, $price->getOriginal($field));
            $to = $this->historyValue($field, $price->getAttribute($field));

            if ($from === $to) {
                continue;
            }

            $changes[$field] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, array{from: ?string, to: ?string}>  $changes
     */
    private function recordHistory(
        BranchProductPrice $price,
        array $changes,
        PriceChangeSource $source,
        ?User $actor,
    ): void {
        if ($changes === []) {
            return;
        }

        BranchProductPriceHistory::query()->create([
            'branch_id' => $price->branch_id,
            'product_id' => $price->product_id,
            'variant_id' => $price->variant_id,
            'source' => $source,
            'changes' => $changes,
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    private function historyValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DiscountType) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (in_array($field, ['buying_price', 'regular_price', 'selling_price', 'discount_price'], true)) {
            return self::nullableMoney($value);
        }

        if ($field === 'discount_type') {
            return (string) $value;
        }

        return (string) $value;
    }

    private function resolveDiscountType(mixed $type, mixed $discountPrice): DiscountType
    {
        if ($type instanceof DiscountType) {
            return $type;
        }

        if (is_string($type) && $type !== '') {
            return DiscountType::from($type);
        }

        return $discountPrice !== null && $discountPrice !== ''
            ? DiscountType::Fixed
            : DiscountType::None;
    }

    private static function nullableMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
