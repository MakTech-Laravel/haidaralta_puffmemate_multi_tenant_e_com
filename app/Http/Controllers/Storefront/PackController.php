<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront\StorefrontCatalog;
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
}
