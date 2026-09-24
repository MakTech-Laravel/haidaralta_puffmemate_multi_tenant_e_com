<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePriceRangeRequest;
use App\Http\Requests\Platform\UpdatePriceRangeRequest;
use App\Http\Requests\Platform\UpdateShopPriceFilterBoundsRequest;
use App\Models\PriceRange;
use App\Services\Catalog\PriceRangeService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriceRangeController extends Controller
{
    public function __construct(
        private readonly PriceRangeService $priceRanges,
    ) {}

    public function index(): Response
    {
        return Inertia::render('platform/catalog/price-ranges/index', [
            'priceRanges' => $this->priceRanges->indexPayload(),
            'sliderBounds' => $this->priceRanges->boundsPayload(),
        ]);
    }

    public function updateBounds(UpdateShopPriceFilterBoundsRequest $request): RedirectResponse
    {
        $this->priceRanges->updateSliderBounds($request->validated());

        Toast::success('Shop price slider bounds saved.');

        return redirect()->route('platform.price-ranges.index');
    }

    public function create(): Response
    {
        return Inertia::render('platform/catalog/price-ranges/form', [
            'priceRange' => null,
        ]);
    }

    public function store(StorePriceRangeRequest $request): RedirectResponse
    {
        $this->priceRanges->create($request->validated());

        Toast::success('Price range added.');

        return redirect()->route('platform.price-ranges.index');
    }

    public function edit(PriceRange $priceRange): Response
    {
        return Inertia::render('platform/catalog/price-ranges/form', [
            'priceRange' => $this->priceRanges->formPayload($priceRange),
        ]);
    }

    public function update(UpdatePriceRangeRequest $request, PriceRange $priceRange): RedirectResponse
    {
        $this->priceRanges->update($priceRange, $request->validated());

        Toast::success('Price range saved.');

        return redirect()->route('platform.price-ranges.index');
    }

    public function destroy(PriceRange $priceRange): RedirectResponse
    {
        $this->priceRanges->delete($priceRange);

        Toast::success('Price range removed.');

        return redirect()->route('platform.price-ranges.index');
    }

    public function move(Request $request, PriceRange $priceRange): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $this->priceRanges->move($priceRange, $direction);

        Toast::success('Price range order updated.');

        return redirect()->route('platform.price-ranges.index');
    }

    public function toggleStatus(Request $request, PriceRange $priceRange): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        $status = $request->boolean('status');
        $this->priceRanges->setStatus($priceRange, $status);

        Toast::success($status ? 'Price range is now visible on shop.' : 'Price range is now hidden.');

        return redirect()->route('platform.price-ranges.index');
    }
}
