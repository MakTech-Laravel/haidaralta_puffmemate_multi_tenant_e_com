<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreProductPackageRequest;
use App\Http\Requests\Platform\UpdateProductPackageRequest;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Services\Catalog\ProductPackageService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class ProductPackageController extends Controller
{
    public function __construct(private ProductPackageService $packages) {}

    public function store(StoreProductPackageRequest $request, Product $product): RedirectResponse
    {
        abort_unless((bool) config('catalog.show_product_packages'), 404);

        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        $this->packages->create($product, $data);

        Toast::success('Package created.');

        return back();
    }

    public function update(
        UpdateProductPackageRequest $request,
        Product $product,
        ProductPackage $package,
    ): RedirectResponse {
        abort_unless((bool) config('catalog.show_product_packages'), 404);
        abort_unless($package->product_id === $product->id, 404);

        $data = $request->validated();
        $data['status'] = $request->boolean('status', true);

        $this->packages->update($package, $data);

        Toast::success('Package updated.');

        return back();
    }

    public function destroy(Product $product, ProductPackage $package): RedirectResponse
    {
        abort_unless((bool) config('catalog.show_product_packages'), 404);
        abort_unless($package->product_id === $product->id, 404);

        $this->packages->delete($package);

        Toast::success('Package deleted.');

        return back();
    }
}
