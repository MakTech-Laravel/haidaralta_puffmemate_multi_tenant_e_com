<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductPackage;
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
                'sort_order' => array_key_exists('sort_order', $data)
                    ? (int) $data['sort_order']
                    : $this->nextSortOrder($product),
            ]);
        });
    }

    /**
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

    public function delete(ProductPackage $package): void
    {
        $package->delete();
    }

    /**
     * Replace a product's packages with the submitted rows.
     *
     * Rows with a matching product package id are updated; others are created.
     * Existing packages missing from the payload are soft-deleted.
     *
     * @param  list<array{
     *     id?: int|string|null,
     *     label: string,
     *     quantity: int|string,
     *     price: float|string,
     *     status?: bool,
     *     sort_order?: int|string
     * }>  $packages
     */
    public function syncForProduct(Product $product, array $packages): void
    {
        DB::transaction(function () use ($product, $packages): void {
            $existing = ProductPackage::query()
                ->where('product_id', $product->id)
                ->get()
                ->keyBy('id');

            $keptIds = [];

            foreach (array_values($packages) as $index => $row) {
                $id = isset($row['id']) ? (int) $row['id'] : 0;
                $payload = [
                    'label' => $row['label'],
                    'quantity' => (int) $row['quantity'],
                    'price' => $row['price'],
                    'status' => array_key_exists('status', $row)
                        ? (bool) $row['status']
                        : true,
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                ];

                if ($id > 0 && $existing->has($id)) {
                    $this->update($existing->get($id), $payload);
                    $keptIds[] = $id;

                    continue;
                }

                $created = $this->create($product, $payload);
                $keptIds[] = $created->id;
            }

            foreach ($existing as $package) {
                if (! in_array($package->id, $keptIds, true)) {
                    $this->delete($package);
                }
            }
        });
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
