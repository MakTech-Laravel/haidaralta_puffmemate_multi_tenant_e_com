<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class ProductDataTable extends AbstractDataTable
{
    /**
     * @return class-string<Product>
     */
    protected function modelClass(): string
    {
        return Product::class;
    }

    /**
     * @return Builder<Product>
     */
    protected function baseQuery(): Builder
    {
        return Product::query()
            ->with('categories:id,name,slug')
            ->withCount('variants');
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::callback('category_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->whereHas(
                    'categories',
                    fn (Builder $categories): Builder => $categories->where('categories.id', (int) $value),
                );
            }),
            StatusBooleanFilter::make('status'),
            $this->searchFilter(),
            AllowedFilter::trashed(),
        ];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'sku',
            'status',
            'created_at',
            'variants_count',
            AllowedSort::callback('category_name', function (Builder $query, bool $descending): void {
                $query->orderBy(
                    Category::query()
                        ->select('categories.name')
                        ->join('category_product', 'categories.id', '=', 'category_product.category_id')
                        ->whereColumn('category_product.product_id', 'products.id')
                        ->orderBy('categories.name')
                        ->limit(1),
                    $descending ? 'desc' : 'asc',
                );
            }),
        ];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
        ];
    }

    /**
     * @param  Product  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Product $model */
        return [
            'id' => $model->id,
            'category_ids' => $model->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'categories' => $model->categories
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
                ->all(),
            'category_name' => $model->categoryNamesLabel(),
            'name' => $model->name,
            'slug' => $model->slug,
            'sku' => $model->sku,
            'image' => $model->image,
            'image_url' => $model->imageUrl(),
            'status' => (bool) $model->status,
            'variants_count' => (int) ($model->variants_count ?? 0),
            'deleted_at' => $model->deleted_at?->toIso8601String(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
