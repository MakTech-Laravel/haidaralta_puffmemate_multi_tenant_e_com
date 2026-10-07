<?php

namespace App\Support\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverInventory;
use App\Models\Product;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Driver\DriverInventorySaleService;
use App\Support\BranchProductPriceDisplay;
use Illuminate\Support\Collection;

class DriverPlaceOrderCatalog
{
    /**
     * @return array{
     *     customers: list<array<string, mixed>>,
     *     products: list<array{
     *         id: string,
     *         name: string,
     *         description: string,
     *         sku: string,
     *         category: string,
     *         priceLabel: string,
     *         price: float,
     *         stock: int,
     *         image: string
     *     }>,
     *     paymentMethods: list<array{id: string, label: string, description?: string|null, badge?: string, title?: string}>,
     *     canSell: bool
     * }
     */
    public static function forDriver(User $driver): array
    {
        $branchId = self::activeBranchId($driver);
        $payments = app(PaymentGatewaySettingsService::class);
        $payments->ensureDefaults();

        return [
            'products' => $branchId === null ? [] : self::productsForDriver($driver, $branchId),
            'paymentMethods' => $payments->enabledMethods(),
            'canSell' => $branchId !== null,
        ];
    }

    public static function activeBranchId(User $driver): ?int
    {
        $ids = DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->pluck('branch_id')
            ->unique()
            ->values();

        if ($ids->count() !== 1) {
            return null;
        }

        return (int) $ids->first();
    }

    /**
     * @return list<array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     sku: string,
     *     category: string,
     *     priceLabel: string,
     *     price: float,
     *     stock: int,
     *     image: string
     * }>
     */
    public static function productsForDriver(User $driver, ?int $branchId = null): array
    {
        $branchId ??= self::activeBranchId($driver);

        if ($branchId === null) {
            return [];
        }

        $branch = Branch::query()->find($branchId);

        if ($branch === null) {
            return [];
        }

        /** @var Collection<int, DriverInventory> $rows */
        $rows = DriverInventory::query()
            ->where('user_id', $driver->id)
            ->where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->with([
                'product.categories:id,name',
                'product.media',
                'variant:id,name,sku',
            ])
            ->get();

        /** @var Collection<string, array{product: Product, variantName: ?string, variantSku: ?string, stock: int}> $aggregated */
        $aggregated = collect();

        foreach ($rows as $row) {
            $product = $row->product;

            if ($product === null || ! $product->status) {
                continue;
            }

            $key = $row->variant_id === null
                ? (string) $row->product_id
                : $row->product_id.':'.$row->variant_id;

            $existing = $aggregated->get($key);

            if ($existing === null) {
                $aggregated->put($key, [
                    'product' => $product,
                    'variantName' => $row->variant?->name,
                    'variantSku' => $row->variant?->sku,
                    'stock' => $row->quantity,
                ]);

                continue;
            }

            $existing['stock'] += $row->quantity;
            $aggregated->put($key, $existing);
        }

        return $aggregated
            ->map(function (array $item, string $id) use ($branch): array {
                /** @var Product $product */
                $product = $item['product'];
                $price = app(DriverInventorySaleService::class)->unitPrice($branch, $product) ?? 0.0;
                $name = $product->name;

                if (is_string($item['variantName']) && $item['variantName'] !== '') {
                    $name .= ' · '.$item['variantName'];
                }

                $sku = (string) ($item['variantSku'] ?? $product->sku ?? '');
                $description = filled($product->description)
                    ? str(strip_tags((string) $product->description))->limit(120)->toString()
                    : '';

                $category = $product->categories->first()?->name ?? 'Uncategorized';

                return [
                    'id' => $id,
                    'name' => $name,
                    'description' => $description,
                    'sku' => $sku,
                    'category' => $category,
                    'priceLabel' => BranchProductPriceDisplay::formatAud($price),
                    'price' => $price,
                    'stock' => (int) $item['stock'],
                    'image' => $product->imageUrl() ?? '',
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
