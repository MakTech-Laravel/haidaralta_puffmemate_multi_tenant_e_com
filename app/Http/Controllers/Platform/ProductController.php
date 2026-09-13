<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\ProductDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreProductRequest;
use App\Http\Requests\Platform\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductSuggestionService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $products,
        private ProductSuggestionService $suggestions,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/catalog/products/index', [
            'table' => ProductDataTable::make($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/catalog/products/create', [
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();
        $excludeProductId = $request->integer('exclude') ?: null;

        return response()->json([
            'suggestions' => $this->suggestions->suggest(
                $query,
                excludeProductId: $excludeProductId,
            ),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'gallery']);
        $data['status'] = $request->boolean('status', true);

        $product = $this->products->createFromPlatform(
            $data,
            $request->file('image'),
            $this->uploadedGallery($request),
            $request->user('platform'),
        );

        Toast::success('Product created. Disabled for all branches — enable from branch inventory.');

        return redirect()->route('platform.products.show', $product);
    }

    public function show(Product $product): Response
    {
        $product->load(['categories:id,name,slug', 'variants', 'images']);

        return Inertia::render('platform/catalog/products/show', [
            'product' => $this->mapProduct($product, detailed: true),
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load(['categories:id,name,slug', 'images']);

        return Inertia::render('platform/catalog/products/edit', [
            'product' => $this->mapProduct($product, detailed: true),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'gallery', 'remove_gallery_ids']);
        $data['status'] = $request->boolean('status', true);
        $data['remove_image'] = $request->boolean('remove_image');
        $data['remove_gallery_ids'] = collect($request->input('remove_gallery_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->all();

        $product = $this->products->update(
            $product,
            $data,
            $request->file('image'),
            $this->uploadedGallery($request),
        );

        Toast::success('Product updated.');

        return redirect()->route('platform.products.show', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        Toast::success('Product deleted.');

        return redirect()->route('platform.products.index');
    }

    public function restore(Product $product): RedirectResponse
    {
        abort_unless($product->trashed(), 404);

        $this->products->restore($product);

        Toast::success('Product restored.');

        return redirect()->route('platform.products.show', $product);
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
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
     * @return list<UploadedFile>
     */
    private function uploadedGallery(Request $request): array
    {
        $files = $request->file('gallery', []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter(
            is_array($files) ? $files : [],
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProduct(Product $product, bool $detailed = false): array
    {
        $payload = [
            'id' => $product->id,
            'category_ids' => $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'categories' => $product->categories
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
                ->all(),
            'category_name' => $product->categoryNamesLabel(),
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'image' => $product->image,
            'image_url' => $product->imageUrl(),
            'status' => (bool) $product->status,
            'created_at' => $product->created_at?->toIso8601String(),
            'gallery' => $product->relationLoaded('images')
                ? $product->images
                    ->map(fn (ProductImage $image): array => [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => $image->url(),
                        'sort_order' => $image->sort_order,
                        'mime_type' => 'image/jpeg',
                    ])
                    ->values()
                    ->all()
                : [],
        ];

        if ($detailed) {
            $payload['variants'] = $product->relationLoaded('variants')
                ? $product->variants
                    ->map(fn (ProductVariant $variant): array => [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                        'attributes' => $variant->attributes,
                        'status' => (bool) $variant->status,
                    ])
                    ->values()
                    ->all()
                : [];
            $payload['enablement_notice'] = 'Disabled for all branches until enabled from branch inventory.';
        }

        return $payload;
    }
}
