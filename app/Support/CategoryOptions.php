<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class CategoryOptions
{
    /**
     * @return array{
     *     data: list<array{id: int, name: string, slug: string}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public static function paginated(Request $request): array
    {
        $search = trim((string) (
            $request->input('filter.search')
            ?? $request->query('search', '')
        ));
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));

        $query = Category::query()->orderBy('name')->orderBy('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return [
            'data' => collect($paginator->items())->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()->all(),
            'meta' => self::meta($paginator),
        ];
    }

    /**
     * @param  list<int|string>|int|string|null  $value
     * @return list<int>
     */
    public static function normalizeIds(mixed $value): array
    {
        if ($value === null || $value === '' || $value === 'all') {
            return [];
        }

        $ids = is_array($value) ? $value : [$value];

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @param  list<int>  $ids
     * @return list<array{id: int, name: string, slug: string}>
     */
    public static function selected(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Category::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array{current_page: int, last_page: int, per_page: int, total: int}
     */
    private static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
