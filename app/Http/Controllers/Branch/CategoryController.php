<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchCategoryDataTable;
use App\Http\Controllers\Concerns\ManagesCategoryMerchandising;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreCategoryRequest;
use App\Http\Requests\Branch\UpdateCategoryRequest;
use App\Http\Requests\Catalog\UpdateCategoryNavSortRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\CategorySuggestionService;
use App\Support\CategoryOptions;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use ManagesCategoryMerchandising;

    public function __construct(
        private CategoryService $categories,
        private CategorySuggestionService $suggestions,
    ) {}

    public function index(Request $request): Response
    {
        $this->abortUnlessBranchCanViewCategories();

        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/categories/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchCategoryDataTable::make($request),
        ]);
    }

    public function create(): Response
    {
        $this->abortUnlessBranchCanViewCategories();
        abort_unless((bool) config('catalog.branch_can_create_categories'), 404);

        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/categories/create', [
            'branch' => $branch->toPortalContext(),
            'parentOptions' => $this->parentOptions(),
            'limits' => $this->categories->limits(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $this->abortUnlessBranchCanViewCategories();

        $query = $request->string('q')->trim()->toString();
        $excludeCategoryId = $request->integer('exclude') ?: null;

        return response()->json([
            'suggestions' => $this->suggestions->suggest(
                $query,
                excludeCategoryId: $excludeCategoryId,
            ),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        return response()->json(CategoryOptions::paginated($request));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->abortUnlessBranchCanViewCategories();
        abort_unless((bool) config('catalog.branch_can_create_categories'), 404);

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
        $this->abortUnlessBranchCanViewCategories();

        $branch = $this->currentBranch();

        $category->load(['parent:id,name,slug', 'children:id,name,slug,parent_id,status'])
            ->loadCount(['products', 'children']);

        return Inertia::render('branch/catalog/categories/show', [
            'branch' => $branch->toPortalContext(),
            'category' => $this->mapCategory($category, detailed: true),
        ]);
    }

    public function edit(string $branchSlug, Category $category): Response
    {
        $this->abortUnlessBranchCanViewCategories();

        $branch = $this->currentBranch();

        return Inertia::render('branch/catalog/categories/edit', [
            'branch' => $branch->toPortalContext(),
            'category' => $this->mapCategory($category),
            'parentOptions' => $this->parentOptions($category->id),
            'limits' => $this->categories->limits($category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, string $branchSlug, Category $category): RedirectResponse
    {
        $this->abortUnlessBranchCanViewCategories();

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

    public function toggleStatus(string $branchSlug, Category $category): RedirectResponse
    {
        $this->abortUnlessBranchCanViewCategories();

        return $this->merchandiseToggleStatus($category);
    }

    public function toggleFeatured(string $branchSlug, Category $category): RedirectResponse
    {
        $this->abortUnlessBranchCanViewCategories();

        return $this->merchandiseToggleFeatured($category);
    }

    public function toggleNav(string $branchSlug, Category $category): RedirectResponse
    {
        $this->abortUnlessBranchCanViewCategories();

        return $this->merchandiseToggleNav($category);
    }

    public function updateNavSort(UpdateCategoryNavSortRequest $request, string $branchSlug, Category $category): RedirectResponse
    {
        $this->abortUnlessBranchCanViewCategories();

        return $this->merchandiseUpdateNavSort($request, $category);
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
            'updated_at' => $category->updated_at?->toIso8601String(),
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

    private function abortUnlessBranchCanViewCategories(): void
    {
        abort_unless((bool) config('catalog.branch_can_view_categories'), 404);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
