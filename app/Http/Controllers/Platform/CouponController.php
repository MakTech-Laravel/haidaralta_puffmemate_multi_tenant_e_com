<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCouponRequest;
use App\Http\Requests\Platform\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\Catalog\CouponAdminService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponAdminService $coupons,
    ) {}

    public function index(): Response
    {
        return Inertia::render('platform/coupons/index', [
            'coupons' => $this->coupons->indexPayload(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/coupons/form', [
            'coupon' => null,
        ]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $this->coupons->create($request->validated());

        Toast::success('Coupon added.');

        return redirect()->route('platform.coupons.index');
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('platform/coupons/form', [
            'coupon' => $this->coupons->formPayload($coupon),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->coupons->update($coupon, $request->validated());

        Toast::success('Coupon saved.');

        return redirect()->route('platform.coupons.index');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->coupons->delete($coupon);

        Toast::success('Coupon removed.');

        return redirect()->route('platform.coupons.index');
    }
}
