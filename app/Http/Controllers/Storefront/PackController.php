<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\BuyPackRequest;
use App\Support\Storefront\PackCheckoutSession;
use App\Support\Storefront\StorefrontCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PackController extends Controller
{
    /**
     * Display a storefront pack details page.
     */
    public function show(string $branch, string $pack): Response
    {
        $model = StorefrontCatalog::findActivePack($branch, $pack);

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        $branchModel = $model->branch;
        $packCount = StorefrontCatalog::activePackCount($branchModel);

        return Inertia::render('storefront/pack', [
            'pack' => StorefrontCatalog::packDetail($model),
            'relatedPacks' => StorefrontCatalog::relatedPacks($model),
            'backHref' => route('packs-saves', ['branch' => $branchModel->slug], false),
            'backLabel' => sprintf(
                'Back to %s Packs & Saves (%d %s)',
                $branchModel->name,
                $packCount,
                $packCount === 1 ? 'pack' : 'packs',
            ),
        ]);
    }

    /**
     * Start pack buy-now checkout (session only — no cart).
     */
    public function buy(BuyPackRequest $request, string $branch, string $pack): RedirectResponse
    {
        $model = StorefrontCatalog::findActivePack($branch, $pack);

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        $quantity = (int) $request->validated('quantity');

        if ($quantity > $model->quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Only '.$model->quantity.' pack(s) left.',
            ]);
        }

        PackCheckoutSession::put($request, $model, $quantity);

        return redirect()->route('checkout');
    }
}
