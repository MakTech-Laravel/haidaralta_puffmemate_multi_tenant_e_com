<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreProductVariantRequest;
use App\Http\Requests\Platform\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\VariantService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function __construct(private VariantService $variants) {}

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        $this->variants->create($product, $data);

        Toast::success('Variant created. It is not enabled at any branch until inventory enablement.');

        return redirect()->route('platform.products.show', $product);
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
    ): RedirectResponse {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        $this->variants->update($variant, $data);

        Toast::success('Variant updated.');

        return redirect()->route('platform.products.show', $product);
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $this->variants->delete($variant);

        Toast::success('Variant deleted.');

        return redirect()->route('platform.products.show', $product);
    }
}
