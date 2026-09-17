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
            return redirect()->to(self::platformUrl($branch, $product, self::lockedFrom($request)), $status);
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
            return redirect()->to(self::branchUrl($branch, $product, self::lockedFrom($request)), $status);
        }

        return redirect()->route('branch.products.index', $branch, $status);
    }

    public static function platformUrl(Branch $branch, Product $product, ?string $locked = 'both'): string
    {
        return route('platform.stock.manage', self::pairQuery($branch, $product, $locked));
    }

    public static function branchUrl(Branch $branch, Product $product, ?string $locked = 'both'): string
    {
        return route('branch.stock.manage', [
            'branch' => $branch,
            'product' => $product->id,
            ...($locked ? ['locked' => $locked] : []),
        ]);
    }

    /**
     * @return array{branch: int, product: int, locked?: string}
     */
    public static function pairQuery(Branch $branch, Product $product, ?string $locked = 'both'): array
    {
        $query = [
            'branch' => $branch->id,
            'product' => $product->id,
        ];

        if (is_string($locked) && $locked !== '') {
            $query['locked'] = $locked;
        }

        return $query;
    }

    private static function lockedFrom(Request $request): ?string
    {
        $locked = $request->query('locked');

        return in_array($locked, ['product', 'branch', 'both'], true) ? $locked : null;
    }
}
