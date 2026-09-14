<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class CategoryDataTable extends AbstractDataTable
{
    /**
     * @return class-string<Category>
     */
    protected function modelClass(): string
    {
        return Category::class;
    }

    /**
     * @return Builder<Category>
     */
    protected function baseQuery(): Builder
    {
        return Category::query()
            ->with('parent:id,name,slug')
            ->withCount('products');
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('parent_id'),
            StatusBooleanFilter::make('status'),
            StatusBooleanFilter::make('featured'),
            StatusBooleanFilter::make('show_in_nav'),
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
            'slug',
            'status',
            'featured',
            'show_in_nav',
            'nav_sort',
            'created_at',
            'products_count',
            AllowedSort::callback('parent_name', function (Builder $query, bool $descending): void {
                $query->orderBy(
                    Category::query()
                        ->select('name')
                        ->whereColumn('categories.id', 'categories.parent_id')
                        ->limit(1),
                    $descending ? 'desc' : 'asc',
                );
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'parents' => Category::query()
                ->whereNull('parent_id')
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
     * @param  Category  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Category $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'parent_id' => $model->parent_id,
            'parent_name' => $model->parent?->name,
            'image' => $model->image,
            'image_url' => $model->imageUrl(),
            'slogan' => $model->slogan,
            'color_code' => $model->color_code,
            'status' => (bool) $model->status,
            'featured' => (bool) $model->featured,
            'show_in_nav' => (bool) $model->show_in_nav,
            'nav_sort' => $model->nav_sort,
            'products_count' => (int) ($model->products_count ?? 0),
            'deleted_at' => $model->deleted_at?->toIso8601String(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
