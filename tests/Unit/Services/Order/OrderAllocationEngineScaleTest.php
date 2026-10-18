<?php

use App\Enums\CartStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\OrderAllocationEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;

/**
 * Regression guard for the O(branches x lines x branches log branches) sorting
 * problem the BranchDistanceMatrix fix (Section 7 of the allocation engine plan)
 * removed. At the stated production scale (50-100 branches, 2500+ products) a
 * single allocate() call must stay well under a second even on this shared,
 * unoptimised SQLite test runner. Parallel runs share the CPU across workers,
 * so they get a wider budget; the fastest of three calls filters scheduler noise.
 */
test('allocation stays fast at production scale: 100 branches and a 10-line cart', function () {
    $branches = Branch::factory()->count(100)->create([
        'status' => true,
        'delivery_radius_km' => null,
    ]);

    // Spread branches across a small grid so distances vary, exercising the
    // distance matrix's full N^2 branch-pair computation.
    foreach ($branches as $index => $branch) {
        $branch->forceFill([
            'latitude' => -33.0 - ($index % 10) * 0.1,
            'longitude' => 151.0 + intdiv($index, 10) * 0.1,
        ])->save();
    }

    // Bulk-insert directly rather than using the factory: fake()->unique() word
    // combinations for name/slug/sku exhaust their retry budget well before 2500
    // rows, and per-row category attachment isn't needed for this engine test.
    $now = now();
    $rows = [];

    for ($i = 0; $i < 2500; $i++) {
        $rows[] = [
            'name' => 'Scale Test Product '.$i,
            'slug' => 'scale-test-product-'.$i,
            'sku' => 'SCALE-'.$i,
            'discount_type' => 'none',
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 200) as $chunk) {
        DB::table('products')->insert($chunk);
    }

    $products = Product::query()->where('slug', 'like', 'scale-test-product-%')->get();

    // Only a handful of branches stock the 10 products the cart actually needs,
    // forcing the planner to evaluate transfer options across the other branches.
    $cartProducts = $products->take(10);
    $stockedBranches = $branches->take(5);

    foreach ($cartProducts as $productIndex => $product) {
        $stockBranch = $stockedBranches[$productIndex % $stockedBranches->count()];
        Inventory::factory()->create([
            'branch_id' => $stockBranch->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'status' => true,
        ]);
    }

    $cart = Cart::query()->create(['user_id' => User::factory()->create()->id, 'status' => CartStatus::Active]);
    $items = $cartProducts->map(fn (Product $product): CartItem => CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 1,
        'price_snapshot' => 10,
    ]));

    $engine = app(OrderAllocationEngine::class);
    $fastestMs = INF;

    foreach (range(1, 3) as $attempt) {
        $start = microtime(true);
        $plan = $engine->allocate(-33.0, 151.0, $items);
        $fastestMs = min($fastestMs, (microtime(true) - $start) * 1000);
    }

    $budgetMs = ParallelTesting::token() === false ? 1000.0 : 2500.0;

    expect($plan['branch'])->not->toBeNull()
        ->and($fastestMs)->toBeLessThan($budgetMs);
});
