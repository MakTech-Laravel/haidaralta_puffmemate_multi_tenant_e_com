<?php

namespace App\Support\Driver;

use App\Models\DriverInventory;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchProductPriceDisplay;
use App\Support\Storefront\DemoCatalog;
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
     *         category: string,
     *         priceLabel: string,
     *         price: float,
     *         stock: int,
     *         image: string
     *     }>,
     *     paymentMethods: list<array{id: string, label: string}>
     * }
     */
    public static function forDriver(User $driver): array
    {
        $demo = DemoCatalog::driverPlaceOrder();

        return [
            'customers' => $demo['customers'],
            'products' => self::productsForDriver($driver),
            'paymentMethods' => $demo['paymentMethods'],
        ];
    }

    /**
     * @return list<array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     priceLabel: string,
     *     price: float,
     *     stock: int,
     *     image: string
     * }>
     */
    public static function productsForDriver(User $driver): array
    {
        /** @var Collection<int, DriverInventory> $rows */
        $rows = DriverInventory::query()
            ->where('user_id', $driver->id)
            ->where('quantity', '>', 0)
            ->with([
                'product.categories:id,name',
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
            ->map(function (array $item, string $id): array {
                /** @var Product $product */
                $product = $item['product'];
                $display = BranchProductPriceDisplay::forCatalogProduct($product);
                $price = $display['effective']
                    ?? $display['selling']
                    ?? (float) ($product->selling_price ?? 0);
                $name = $product->name;

                if (is_string($item['variantName']) && $item['variantName'] !== '') {
                    $name .= ' · '.$item['variantName'];
                }

                $description = $item['variantSku']
                    ?? $product->sku
                    ?? '';

                if ($description === '' && filled($product->description)) {
                    $description = str(strip_tags((string) $product->description))->limit(120)->toString();
                }

                $category = $product->categories->first()?->name ?? 'Uncategorized';

                return [
                    'id' => $id,
                    'name' => $name,
                    'description' => (string) $description,
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
