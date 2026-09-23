<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductManageRedirect
{
    public static function platform(Request $request, int $status = 302): RedirectResponse
    {
        $branch = StockPageContext::optionalBranch($request);
        $product = StockPageContext::optionalProduct($request);

        if ($product !== null && $branch !== null) {
            return redirect()->to(self::platformUrl($branch, $product), $status);
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
            return redirect()->to(self::branchUrl($branch, $product), $status);
        }

        return redirect()->route('branch.products.index', $branch, $status);
    }

    public static function platformUrl(Branch $branch, Product $product): string
    {
        return route('platform.stock.manage', self::pairQuery($branch, $product));
    }

    public static function branchUrl(Branch $branch, Product $product): string
    {
        return route('branch.stock.manage', [
            'branch' => $branch,
            'product' => $product->id,
        ]);
    }

    /**
     * @return array{branch: int, product: int}
     */
    public static function pairQuery(Branch $branch, Product $product): array
    {
        return [
            'branch' => $branch->id,
            'product' => $product->id,
        ];
    }
}
