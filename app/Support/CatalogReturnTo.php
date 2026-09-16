<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CatalogReturnTo
{
    public static function fromRequest(Request $request): ?string
    {
        $value = $request->string('return_to')->toString();

        if ($value === '' || ! str_starts_with($value, '/')) {
            return null;
        }

        if (str_contains($value, '//') || str_contains($value, "\n") || str_contains($value, "\r")) {
            return null;
        }

        if (! str_starts_with($value, '/platform/') && ! str_starts_with($value, '/branch/')) {
            return null;
        }

        return $value;
    }

    public static function currentUrl(Request $request): string
    {
        $uri = $request->getRequestUri();

        return str_starts_with($uri, '/') ? $uri : '/'.$uri;
    }

    public static function afterProductCreate(
        Request $request,
        Product $product,
        string $fallbackRoute,
        mixed $fallbackParams = [],
    ): RedirectResponse {
        $returnTo = self::fromRequest($request);

        if ($returnTo === null) {
            return redirect()->route($fallbackRoute, $fallbackParams);
        }

        $parts = parse_url($returnTo);
        $path = $parts['path'] ?? $returnTo;
        parse_str($parts['query'] ?? '', $query);
        $query['product'] = (string) $product->id;

        return redirect($path.'?'.http_build_query($query));
    }
}
