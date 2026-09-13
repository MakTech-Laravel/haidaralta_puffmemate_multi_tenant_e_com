<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;
use Throwable;

class ProductSuggestionService
{
    /**
     * @return Collection<int, array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}>
     */
    public function suggest(string $query, int $limit = 8, ?int $excludeProductId = null): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return collect();
        }

        $fromScout = $this->suggestViaScout($query, $limit, $excludeProductId);

        if ($fromScout->isNotEmpty()) {
            return $fromScout;
        }

        return $this->suggestViaDatabase($query, $limit, $excludeProductId);
    }

    /**
     * @return Collection<int, array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}>
     */
    private function suggestViaScout(string $query, int $limit, ?int $excludeProductId): Collection
    {
        try {
            $builder = Product::search($query)->take($limit * 2);

            return $builder
                ->get()
                ->when(
                    $excludeProductId !== null,
                    fn (Collection $products) => $products->reject(
                        fn (Product $product): bool => $product->id === $excludeProductId,
                    ),
                )
                ->take($limit)
                ->map(fn (Product $product): array => $this->mapProduct($product))
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return Collection<int, array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}>
     */
    private function suggestViaDatabase(string $query, int $limit, ?int $excludeProductId): Collection
    {
        $like = '%'.$query.'%';

        return Product::query()
            ->when(
                $excludeProductId !== null,
                fn ($builder) => $builder->whereKeyNot($excludeProductId),
            )
            ->where(function ($builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product): array => $this->mapProduct($product))
            ->values();
    }

    /**
     * @return array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}
     */
    private function mapProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'image_url' => $product->imageUrl(),
        ];
    }
}
