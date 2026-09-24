<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Support\CatalogBackgroundColor;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $category = DB::transaction(function () use ($data, $image): Category {
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

        StorefrontCatalog::forgetNavCategories();

        return $category;
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
        $category = DB::transaction(function () use ($category, $data, $image): Category {
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

        StorefrontCatalog::forgetNavCategories();

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();

        StorefrontCatalog::forgetNavCategories();
    }

    public function restore(Category $category): Category
    {
        $category->restore();

        StorefrontCatalog::forgetNavCategories();

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

    public function toggleStatus(Category $category): Category
    {
        return $this->update($category, ['status' => ! $category->status]);
    }

    public function toggleFeatured(Category $category): Category
    {
        if (! $category->featured) {
            $limits = $this->limits($category->id);

            if ($limits['featured_count'] >= $limits['featured_max']) {
                throw ValidationException::withMessages([
                    'featured' => "Only {$limits['featured_max']} categories can be featured at a time.",
                ]);
            }
        }

        return $this->update($category, ['featured' => ! $category->featured]);
    }

    public function toggleNav(Category $category): Category
    {
        if (! $category->show_in_nav) {
            $limits = $this->limits($category->id);

            if ($limits['nav_count'] >= $limits['nav_max']) {
                throw ValidationException::withMessages([
                    'show_in_nav' => "Only {$limits['nav_max']} categories can appear in the header navigation.",
                ]);
            }
        }

        return $this->update($category, ['show_in_nav' => ! $category->show_in_nav]);
    }

    public function updateNavSort(Category $category, int $navSort): Category
    {
        if (! $category->show_in_nav) {
            throw ValidationException::withMessages([
                'nav_sort' => 'Set the category as available on nav before choosing a position.',
            ]);
        }

        return $this->update($category, ['nav_sort' => $navSort]);
    }
}
