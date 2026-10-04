<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreHomeBannerRequest;
use App\Http\Requests\Platform\UpdateHomeBannerRequest;
use App\Models\HomeBanner;
use App\Services\Cms\HomeBannerService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeBannerController extends Controller
{
    public function __construct(
        private readonly HomeBannerService $banners,
    ) {}

    public function index(): Response
    {
        return Inertia::render('platform/home-banners/index', [
            'banners' => $this->banners->indexPayload(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/home-banners/form', [
            'banner' => null,
        ]);
    }

    public function store(StoreHomeBannerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image']);

        $this->banners->create($data, $request->file('image'));

        Toast::success('Banner added.');

        return redirect()->route('platform.home-banners.index');
    }

    public function edit(HomeBanner $homeBanner): Response
    {
        return Inertia::render('platform/home-banners/form', [
            'banner' => $this->banners->formPayload($homeBanner),
        ]);
    }

    public function update(UpdateHomeBannerRequest $request, HomeBanner $homeBanner): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        $this->banners->update(
            $homeBanner,
            $data,
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        Toast::success('Banner saved.');

        return redirect()->route('platform.home-banners.index');
    }

    public function destroy(HomeBanner $homeBanner): RedirectResponse
    {
        $this->banners->delete($homeBanner);

        Toast::success('Banner removed.');

        return redirect()->route('platform.home-banners.index');
    }

    public function move(Request $request, HomeBanner $homeBanner): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $this->banners->move($homeBanner, $direction);

        Toast::success('Banner order updated.');

        return redirect()->route('platform.home-banners.index');
    }

    public function toggle(Request $request, HomeBanner $homeBanner): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $active = $request->boolean('is_active');

        if ($active && ! $homeBanner->hasMedia(HomeBanner::IMAGE_COLLECTION)) {
            Toast::error('Add an image before activating this banner.');

            return redirect()->route('platform.home-banners.edit', $homeBanner);
        }

        $this->banners->setActive($homeBanner, (bool) $active);

        Toast::success($active ? 'Banner is now visible.' : 'Banner is now hidden.');

        return redirect()->route('platform.home-banners.index');
    }
}
