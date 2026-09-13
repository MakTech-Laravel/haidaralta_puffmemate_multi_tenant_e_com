<?php

namespace App\Services\Catalog;

use App\Models\Category;
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
     *     status?: bool
     * }  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Category
    {
        return DB::transaction(function () use ($data, $image): Category {
            return Category::query()->create([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'image' => $image?->store('categories', 'public'),
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    /**
     * @param  array{
     *     parent_id?: int|null,
     *     name?: string,
     *     slug?: string,
     *     status?: bool,
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
}
