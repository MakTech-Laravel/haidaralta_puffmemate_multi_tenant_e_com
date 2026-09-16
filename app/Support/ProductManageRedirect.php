<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductManageRedirect
{
    public static function platform(Request $request, int $status = 302): RedirectResponse
    {
        $branch = StockPageContext::optionalBranch($request);
        $product = StockPageContext::optionalProduct($request);

        if ($product !== null && $branch !== null) {
            return redirect()->route('platform.branches.products.show', [
                'branch' => $branch,
                'product' => $product,
            ], $status);
        }

        if ($product !== null) {
            return redirect()->route('platform.products.branches', $product, $status);
        }

        return redirect()->route('platform.products.index', [], $status);
    }

    public static function branch(Request $request, Branch $branch, int $status = 302): RedirectResponse
    {
        $product = StockPageContext::optionalProduct($request);

        if ($product !== null) {
            return redirect()->route('branch.products.show', [
                'branch' => $branch,
                'product' => $product,
            ], $status);
        }

        return redirect()->route('branch.products.index', $branch, $status);
    }
}
