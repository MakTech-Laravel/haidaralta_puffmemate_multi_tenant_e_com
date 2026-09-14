<?php

namespace App\Http\Requests\Concerns;

use App\Models\Category;
use Illuminate\Validation\Validator;

trait ValidatesCategoryLimits
{
    protected function validateCategoryLimits(Validator $validator, ?int $excludeCategoryId = null): void
    {
        $validator->after(function (Validator $validator) use ($excludeCategoryId): void {
            if ($this->boolean('featured') && $this->featuredLimitReached($excludeCategoryId)) {
                $max = (int) config('catalog.featured_categories_max');
                $validator->errors()->add(
                    'featured',
                    "Only {$max} categories can be featured at a time.",
                );
            }

            if ($this->boolean('show_in_nav') && $this->navLimitReached($excludeCategoryId)) {
                $max = (int) config('catalog.nav_categories_max');
                $validator->errors()->add(
                    'show_in_nav',
                    "Only {$max} categories can appear in the header navigation.",
                );
            }
        });
    }

    private function featuredLimitReached(?int $excludeCategoryId): bool
    {
        $query = Category::query()->where('featured', true);

        if ($excludeCategoryId !== null) {
            $query->whereKeyNot($excludeCategoryId);
        }

        return $query->count() >= (int) config('catalog.featured_categories_max');
    }

    private function navLimitReached(?int $excludeCategoryId): bool
    {
        $query = Category::query()->where('show_in_nav', true);

        if ($excludeCategoryId !== null) {
            $query->whereKeyNot($excludeCategoryId);
        }

        return $query->count() >= (int) config('catalog.nav_categories_max');
    }
}
