<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
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

        $tokens = $this->tokens($query);

        if ($tokens === []) {
            return collect();
        }

        $fromScout = $this->suggestViaScout($query, $limit * 2, $excludeProductId);
        $fromDatabase = $this->suggestViaDatabase($tokens, $limit * 2, $excludeProductId);

        return $fromScout
            ->concat($fromDatabase)
            ->unique('id')
            ->take($limit)
            ->values();
    }

    /**
     * @return list<string>
     */
    private function tokens(string $query): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', $query) ?: [],
            fn (string $token): bool => $token !== '',
        ));
    }

    /**
     * @return Collection<int, array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}>
     */
    private function suggestViaScout(string $query, int $limit, ?int $excludeProductId): Collection
    {
        try {
            $builder = Product::search($query)->take($limit);

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
     * @param  list<string>  $tokens
     * @return Collection<int, array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}>
     */
    private function suggestViaDatabase(array $tokens, int $limit, ?int $excludeProductId): Collection
    {
        return Product::query()
            ->when(
                $excludeProductId !== null,
                fn (Builder $builder) => $builder->whereKeyNot($excludeProductId),
            )
            ->where(function (Builder $builder) use ($tokens): void {
                foreach ($tokens as $token) {
                    $like = '%'.$token.'%';

                    $builder->where(function (Builder $tokenBuilder) use ($like): void {
                        $tokenBuilder
                            ->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like)
                            ->orWhere('sku', 'like', $like);
                    });
                }
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
