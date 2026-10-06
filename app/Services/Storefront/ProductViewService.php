<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\ProductViewLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductViewService
{
    /**
     * Record a unique product view for the request IP (at most once per 24 hours).
     *
     * @return bool True when products.views was incremented
     */
    public function record(Request $request, Product $product): bool
    {
        $ip = trim((string) $request->ip());

        if ($ip === '') {
            return false;
        }

        return DB::transaction(function () use ($request, $product, $ip): bool {
            $recent = ProductViewLog::query()
                ->where('product_id', $product->id)
                ->where('ip_address', $ip)
                ->where('viewed_at', '>=', now()->subDay())
                ->exists();

            if ($recent) {
                return false;
            }

            ProductViewLog::query()->create([
                'product_id' => $product->id,
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'viewed_at' => now(),
            ]);

            $product->increment('views');

            return true;
        });
    }

    public function pruneOlderThanDays(int $days = 7): int
    {
        return ProductViewLog::query()
            ->where('viewed_at', '<', now()->subDays($days))
            ->delete();
    }
}
