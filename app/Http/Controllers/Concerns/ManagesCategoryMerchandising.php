<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\Catalog\UpdateCategoryNavSortRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * @property CategoryService $categories
 */
trait ManagesCategoryMerchandising
{
    protected function merchandiseToggleStatus(Category $category): RedirectResponse
    {
        $category = $this->categoryService()->toggleStatus($category);

        Toast::success($category->status ? 'Category activated.' : 'Category deactivated.');

        return back();
    }

    protected function merchandiseToggleFeatured(Category $category): RedirectResponse
    {
        return $this->merchandiseSafely(
            fn (): Category => $this->categoryService()->toggleFeatured($category),
            fn (Category $updated): string => $updated->featured
                ? 'Category marked as featured.'
                : 'Category removed from featured.',
        );
    }

    protected function merchandiseToggleNav(Category $category): RedirectResponse
    {
        return $this->merchandiseSafely(
            fn (): Category => $this->categoryService()->toggleNav($category),
            fn (Category $updated): string => $updated->show_in_nav
                ? 'Category is now available on nav.'
                : 'Category hidden from nav.',
        );
    }

    protected function merchandiseUpdateNavSort(UpdateCategoryNavSortRequest $request, Category $category): RedirectResponse
    {
        return $this->merchandiseSafely(
            fn (): Category => $this->categoryService()->updateNavSort(
                $category,
                (int) $request->validated('nav_sort'),
            ),
            fn (): string => 'Nav position updated.',
        );
    }

    /**
     * @param  callable(): Category  $action
     * @param  callable(Category): string  $success
     */
    private function merchandiseSafely(callable $action, callable $success): RedirectResponse
    {
        try {
            $category = $action();
        } catch (ValidationException $exception) {
            Toast::error((string) collect($exception->errors())->flatten()->first());

            return back();
        }

        Toast::success($success($category));

        return back();
    }

    private function categoryService(): CategoryService
    {
        return $this->categories;
    }
}
