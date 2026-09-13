<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductSuggestionService
{
    /**
     * @return Collection<int, array{id: int, name: string, slug: string, sku: ?string, image_url: ?string}>
     */
    public function suggest(string $query, int $limit = 8): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        return Product::search($query)
            ->take($limit)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'image_url' => $product->imageUrl(),
            ])
            ->values();
    }
}
