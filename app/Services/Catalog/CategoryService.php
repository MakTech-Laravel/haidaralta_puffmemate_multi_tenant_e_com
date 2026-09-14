<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Support\CatalogBackgroundColor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    /**
     * @param  array{
     *     parent_id?: int|null,
     *     name: string,
     *     slug: string,
     *     slogan?: string|null,
     *     status?: bool,
     *     featured?: bool,
     *     show_in_nav?: bool,
     *     nav_sort?: int|null,
     *     color_code?: array<string, mixed>|null
     * }  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Category
    {
        return DB::transaction(function () use ($data, $image): Category {
            return Category::query()->create([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'slogan' => $data['slogan'] ?? null,
                'image' => $image?->store('categories', 'public'),
                'color_code' => CatalogBackgroundColor::normalize($data['color_code'] ?? null),
                'status' => $data['status'] ?? true,
                'featured' => $data['featured'] ?? false,
                'show_in_nav' => $data['show_in_nav'] ?? false,
                'nav_sort' => $data['nav_sort'] ?? null,
            ]);
        });
    }

    /**
     * @param  array{
     *     parent_id?: int|null,
     *     name?: string,
     *     slug?: string,
     *     slogan?: string|null,
     *     status?: bool,
     *     featured?: bool,
     *     show_in_nav?: bool,
     *     nav_sort?: int|null,
     *     color_code?: array<string, mixed>|null,
     *     remove_image?: bool
     * }  $data
     */
    public function update(Category $category, array $data, ?UploadedFile $image = null): Category
    {
        return DB::transaction(function () use ($category, $data, $image): Category {
            if (($data['remove_image'] ?? false) === true && $category->image) {
                Storage::disk('public')->delete($category->image);
                $category->image = null;
            }

            if ($image !== null) {
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $category->image = $image->store('categories', 'public');
            }

            unset($data['remove_image']);

            if (array_key_exists('color_code', $data)) {
                $data['color_code'] = CatalogBackgroundColor::normalize(
                    is_array($data['color_code']) ? $data['color_code'] : null,
                );
            }

            $category->fill($data);
            $category->save();

            return $category->refresh();
        });
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->refresh();
    }

    /**
     * @return array{featured_max: int, nav_max: int, featured_count: int, nav_count: int}
     */
    public function limits(?int $excludeCategoryId = null): array
    {
        $featuredQuery = Category::query()->where('featured', true);
        $navQuery = Category::query()->where('show_in_nav', true);

        if ($excludeCategoryId !== null) {
            $featuredQuery->whereKeyNot($excludeCategoryId);
            $navQuery->whereKeyNot($excludeCategoryId);
        }

        return [
            'featured_max' => (int) config('catalog.featured_categories_max'),
            'nav_max' => (int) config('catalog.nav_categories_max'),
            'featured_count' => $featuredQuery->count(),
            'nav_count' => $navQuery->count(),
        ];
    }
}
