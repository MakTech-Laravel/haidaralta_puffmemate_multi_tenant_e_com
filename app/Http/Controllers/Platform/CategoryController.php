<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\CategoryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCategoryRequest;
use App\Http\Requests\Platform\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\CategorySuggestionService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categories,
        private CategorySuggestionService $suggestions,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/catalog/categories/index', [
            'table' => CategoryDataTable::make($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/catalog/categories/create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();
        $excludeCategoryId = $request->integer('exclude') ?: null;

        return response()->json([
            'suggestions' => $this->suggestions->suggest(
                $query,
                excludeCategoryId: $excludeCategoryId,
            ),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data['status'] = $request->boolean('status', true);

        $category = $this->categories->create($data, $request->file('image'));

        Toast::success('Category created.');

        return redirect()->route('platform.categories.show', $category);
    }

    public function show(Category $category): Response
    {
        $category->load(['parent:id,name,slug', 'children:id,name,slug,parent_id,status'])
            ->loadCount(['products', 'children']);

        return Inertia::render('platform/catalog/categories/show', [
            'category' => $this->mapCategory($category, detailed: true),
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('platform/catalog/categories/edit', [
            'category' => $this->mapCategory($category),
            'parentOptions' => $this->parentOptions($category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data['status'] = $request->boolean('status', true);
        $data['remove_image'] = $request->boolean('remove_image');

        $category = $this->categories->update($category, $data, $request->file('image'));

        Toast::success('Category updated.');

        return redirect()->route('platform.categories.show', $category);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        Toast::success('Category deleted.');

        return redirect()->route('platform.categories.index');
    }

    public function restore(Category $category): RedirectResponse
    {
        abort_unless($category->trashed(), 404);

        $this->categories->restore($category);

        Toast::success('Category restored.');

        return redirect()->route('platform.categories.show', $category);
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function parentOptions(?int $excludeId = null): array
    {
        return Category::query()
            ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCategory(Category $category, bool $detailed = false): array
    {
        $payload = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'parent_name' => $category->parent?->name,
            'parent' => $category->parent
                ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                ]
                : null,
            'image' => $category->image,
            'image_url' => $category->imageUrl(),
            'color_code' => $category->color_code,
            'status' => (bool) $category->status,
            'created_at' => $category->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $payload['products_count'] = (int) ($category->products_count ?? $category->products()->count());
            $payload['children_count'] = (int) ($category->children_count ?? $category->children->count());
            $payload['children'] = $category->children
                ->map(fn (Category $child): array => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'status' => (bool) $child->status,
                ])
                ->values()
                ->all();
        }

        return $payload;
    }
}
