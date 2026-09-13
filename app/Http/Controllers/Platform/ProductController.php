<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreProductRequest;
use App\Http\Requests\Platform\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $products = $search !== ''
            ? Product::search($search)->orderBy('name')->paginate(15)
            : Product::query()->with('category:id,name,slug')->orderBy('name')->paginate(15);

        if ($search !== '') {
            $products->getCollection()->load('category:id,name,slug');
        }

        $products = $products
            ->withQueryString()
            ->through(fn (Product $product): array => $this->mapProduct($product));

        return Inertia::render('platform/catalog/products/index', [
            'products' => $products,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/catalog/products/create', [
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data['status'] = $request->boolean('status', true);

        $product = $this->products->createFromPlatform(
            $data,
            $request->file('image'),
            $request->user('platform'),
        );

        Toast::success('Product created. Disabled for all branches — enable from branch inventory.');

        return redirect()->route('platform.products.show', $product);
    }

    public function show(Product $product): Response
    {
        $product->load(['category:id,name,slug', 'variants']);

        return Inertia::render('platform/catalog/products/show', [
            'product' => $this->mapProduct($product, detailed: true),
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load('category:id,name,slug');

        return Inertia::render('platform/catalog/products/edit', [
            'product' => $this->mapProduct($product),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image']);
        $data['status'] = $request->boolean('status', true);
        $data['remove_image'] = $request->boolean('remove_image');

        $product = $this->products->update($product, $data, $request->file('image'));

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
     * @return array<string, mixed>
     */
    private function mapProduct(Product $product, bool $detailed = false): array
    {
        $payload = [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category' => $product->category
                ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ]
                : null,
            'category_name' => $product->category?->name ?? '—',
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'image' => $product->image,
            'image_url' => $product->imageUrl(),
            'status' => (bool) $product->status,
            'created_at' => $product->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $payload['variants'] = $product->variants
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'attributes' => $variant->attributes,
                    'status' => (bool) $variant->status,
                ])
                ->values()
                ->all();
            $payload['enablement_notice'] = 'Disabled for all branches until enabled from branch inventory.';
        }

        return $payload;
    }
}
