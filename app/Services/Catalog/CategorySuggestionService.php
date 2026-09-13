<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class CategorySuggestionService
{
    /**
     * @return Collection<int, array{id: int, name: string, slug: string, image_url: ?string}>
     */
    public function suggest(string $query, int $limit = 8, ?int $excludeCategoryId = null): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return collect();
        }

        $tokens = $this->tokens($query);

        if ($tokens === []) {
            return collect();
        }

        $fromScout = $this->suggestViaScout($query, $limit * 2, $excludeCategoryId);
        $fromDatabase = $this->suggestViaDatabase($tokens, $limit * 2, $excludeCategoryId);

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
     * @return Collection<int, array{id: int, name: string, slug: string, image_url: ?string}>
     */
    private function suggestViaScout(string $query, int $limit, ?int $excludeCategoryId): Collection
    {
        try {
            $builder = Category::search($query)->take($limit);

            return $builder
                ->get()
                ->when(
                    $excludeCategoryId !== null,
                    fn (Collection $categories) => $categories->reject(
                        fn (Category $category): bool => $category->id === $excludeCategoryId,
                    ),
                )
                ->take($limit)
                ->map(fn (Category $category): array => $this->mapCategory($category))
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @param  list<string>  $tokens
     * @return Collection<int, array{id: int, name: string, slug: string, image_url: ?string}>
     */
    private function suggestViaDatabase(array $tokens, int $limit, ?int $excludeCategoryId): Collection
    {
        return Category::query()
            ->when(
                $excludeCategoryId !== null,
                fn (Builder $builder) => $builder->whereKeyNot($excludeCategoryId),
            )
            ->where(function (Builder $builder) use ($tokens): void {
                foreach ($tokens as $token) {
                    $like = '%'.$token.'%';

                    $builder->where(function (Builder $tokenBuilder) use ($like): void {
                        $tokenBuilder
                            ->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
                }
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category): array => $this->mapCategory($category))
            ->values();
    }

    /**
     * @return array{id: int, name: string, slug: string, image_url: ?string}
     */
    private function mapCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image_url' => $category->imageUrl(),
        ];
    }
}
