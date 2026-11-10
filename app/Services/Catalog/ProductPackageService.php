<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductPackage;
use DomainException;
use Illuminate\Support\Facades\DB;

class ProductPackageService
{
    public const DIRECTION_UP = 'up';

    public const DIRECTION_DOWN = 'down';

    /**
     * Create a package. Without an explicit sort order it is appended last.
     *
     * @param  array{
     *     label: string,
     *     quantity: int,
     *     price: float|string,
     *     status?: bool,
     *     sort_order?: int
     * }  $data
     */
    public function create(Product $product, array $data): ProductPackage
    {
        return DB::transaction(function () use ($product, $data): ProductPackage {
            return ProductPackage::query()->create([
                'product_id' => $product->id,
                'label' => $data['label'],
                'quantity' => (int) $data['quantity'],
                'price' => number_format((float) $data['price'], 2, '.', ''),
                'status' => $data['status'] ?? true,
                'is_default' => false,
                'sort_order' => array_key_exists('sort_order', $data)
                    ? (int) $data['sort_order']
                    : $this->nextSortOrder($product),
            ]);
        });
    }

    /**
     * Create the protected single-unit package of a product.
     *
     * The price falls back to the product selling price when none is given.
     */
    public function createDefault(
        Product $product,
        float|string|null $price = null,
        bool $status = true,
        int $sortOrder = 0,
    ): ProductPackage {
        $resolvedPrice = $price === null || $price === ''
            ? (float) $product->selling_price
            : (float) $price;

        return ProductPackage::query()->create([
            'product_id' => $product->id,
            'label' => ProductPackage::DEFAULT_LABEL,
            'quantity' => 1,
            'price' => number_format($resolvedPrice, 2, '.', ''),
            'status' => $status,
            'is_default' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Update a package. The default package only accepts price and status.
     *
     * @param  array{
     *     label?: string,
     *     quantity?: int,
     *     price?: float|string,
     *     status?: bool,
     *     sort_order?: int
     * }  $data
     */
    public function update(ProductPackage $package, array $data): ProductPackage
    {
        unset($data['is_default']);

        if ($package->is_default) {
            unset($data['label'], $data['quantity']);
        }

        if (array_key_exists('price', $data)) {
            $data['price'] = number_format((float) $data['price'], 2, '.', '');
        }

        if (array_key_exists('quantity', $data)) {
            $data['quantity'] = (int) $data['quantity'];
        }

        if (array_key_exists('sort_order', $data)) {
            $data['sort_order'] = (int) $data['sort_order'];
        }

        $package->fill($data);
        $package->save();

        return $package->refresh();
    }

    /**
     * Move a package one position up or down within its product.
     *
     * Sort orders are renumbered sequentially first so ties and gaps cannot
     * make a move a no-op.
     */
    public function move(ProductPackage $package, string $direction): void
    {
        DB::transaction(function () use ($package, $direction): void {
            $ordered = ProductPackage::query()
                ->where('product_id', $package->product_id)
                ->ordered()
                ->lockForUpdate()
                ->get();

            $index = $ordered->search(
                fn (ProductPackage $row): bool => $row->id === $package->id,
            );

            if ($index === false) {
                return;
            }

            $target = $direction === self::DIRECTION_UP ? $index - 1 : $index + 1;
            $ids = $ordered->pluck('id')->all();

            if ($target >= 0 && $target < count($ids)) {
                [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
            }

            $byId = $ordered->keyBy('id');

            foreach ($ids as $position => $id) {
                /** @var ProductPackage $row */
                $row = $byId->get($id);

                if ($row->sort_order !== $position) {
                    $row->forceFill(['sort_order' => $position])->save();
                }
            }
        });
    }

    /**
     * @throws DomainException when the package is the protected default one
     */
    public function delete(ProductPackage $package): void
    {
        if ($package->is_default) {
            throw new DomainException('The default package cannot be deleted. Disable it instead.');
        }

        $package->delete();
    }

    public function restore(ProductPackage $package): ProductPackage
    {
        $package->restore();

        return $package->refresh();
    }

    private function nextSortOrder(Product $product): int
    {
        $max = ProductPackage::query()
            ->where('product_id', $product->id)
            ->max('sort_order');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
