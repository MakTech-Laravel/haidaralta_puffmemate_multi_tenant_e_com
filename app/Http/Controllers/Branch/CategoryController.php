<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchCategoryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreCategoryRequest;
use App\Http\Requests\Branch\UpdateCategoryRequest;
use App\Models\Branch;
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
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/categories/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => BranchCategoryDataTable::make($request),
        ]);
    }

    public function create(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/categories/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'parentOptions' => $this->parentOptions(),
            'limits' => $this->categories->limits(),
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
        $branch = $this->currentBranch();
        $data = $request->safe()->except(['image']);
        $data['status'] = $request->boolean('status', true);
        $data['featured'] = $request->boolean('featured');
        $data['show_in_nav'] = $request->boolean('show_in_nav');

        $category = $this->categories->create($data, $request->file('image'));

        Toast::success('Category created.');

        return redirect()->route('branch.categories.show', [$branch, $category]);
    }

    public function show(string $branchSlug, Category $category): Response
    {
        $branch = $this->currentBranch();

        $category->load(['parent:id,name,slug', 'children:id,name,slug,parent_id,status'])
            ->loadCount(['products', 'children']);

        return Inertia::render('branch/catalog/categories/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'category' => $this->mapCategory($category, detailed: true),
        ]);
    }

    public function edit(string $branchSlug, Category $category): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/categories/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'category' => $this->mapCategory($category),
            'parentOptions' => $this->parentOptions($category->id),
            'limits' => $this->categories->limits($category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, string $branchSlug, Category $category): RedirectResponse
    {
        $branch = $this->currentBranch();
        $data = $request->safe()->except(['image']);
        $data['status'] = $request->boolean('status', true);
        $data['featured'] = $request->boolean('featured');
        $data['show_in_nav'] = $request->boolean('show_in_nav');
        $data['remove_image'] = $request->boolean('remove_image');

        $category = $this->categories->update($category, $data, $request->file('image'));

        Toast::success('Category updated.');

        return redirect()->route('branch.categories.show', [$branch, $category]);
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
            'slogan' => $category->slogan,
            'color_code' => $category->color_code,
            'status' => (bool) $category->status,
            'featured' => (bool) $category->featured,
            'show_in_nav' => (bool) $category->show_in_nav,
            'nav_sort' => $category->nav_sort,
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

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
