<?php

namespace App\Services\Storefront;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    public const SESSION_KEY = 'storefront.cart_session';

    public const MAX_LINE_QUANTITY = 99;

    public function sessionId(Request $request): string
    {
        $existing = $request->session()->get(self::SESSION_KEY);

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $id = (string) Str::uuid();
        $request->session()->put(self::SESSION_KEY, $id);

        return $id;
    }

    public function customer(?Request $request = null): ?User
    {
        $user = Auth::guard('customer')->user();

        return $user instanceof User ? $user : null;
    }

    public function activeCart(Request $request): Cart
    {
        $customer = $this->customer($request);

        if ($customer !== null) {
            return Cart::query()->firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'status' => CartStatus::Active,
                ],
                ['session_id' => null],
            );
        }

        return Cart::query()->firstOrCreate(
            [
                'session_id' => $this->sessionId($request),
                'status' => CartStatus::Active,
                'user_id' => null,
            ],
        );
    }

    public function add(Request $request, Product $product, int $quantity = 1, ?int $variantId = null): Cart
    {
        if (! $product->status) {
            throw new InvalidArgumentException('This product is not available.');
        }

        $price = BranchProductPriceDisplay::catalogEffectiveAmount($product);

        if ($price <= 0) {
            throw new InvalidArgumentException('This product cannot be added to the cart.');
        }

        $cart = $this->activeCart($request);
        $quantity = max(1, $quantity);

        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variantId,
        ]);

        $item->quantity = $this->clampLineQuantity(($item->exists ? $item->quantity : 0) + $quantity);
        $item->price_snapshot = $price;
        $item->save();

        return $cart->refresh();
    }

    public function updateQuantity(Request $request, CartItem $item, int $quantity): Cart
    {
        $cart = $this->activeCart($request);

        if ($item->cart_id !== $cart->id) {
            throw new InvalidArgumentException('That cart item does not belong to this cart.');
        }

        if ($quantity < 1) {
            $item->delete();

            return $cart->refresh();
        }

        $item->forceFill(['quantity' => $this->clampLineQuantity($quantity)])->save();

        return $cart->refresh();
    }

    public function remove(Request $request, CartItem $item): Cart
    {
        return $this->updateQuantity($request, $item, 0);
    }

    public function mergeGuestCartIntoUser(Request $request, User $user): Cart
    {
        $sessionId = $request->session()->get(self::SESSION_KEY);
        $userCart = Cart::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => CartStatus::Active,
            ],
            ['session_id' => null],
        );

        if (! is_string($sessionId) || $sessionId === '') {
            return $userCart;
        }

        $guestCart = Cart::query()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->where('status', CartStatus::Active)
            ->first();

        if ($guestCart === null || $guestCart->id === $userCart->id) {
            return $userCart;
        }

        DB::transaction(function () use ($guestCart, $userCart): void {
            foreach ($guestCart->items as $guestItem) {
                $existing = CartItem::query()->firstOrNew([
                    'cart_id' => $userCart->id,
                    'product_id' => $guestItem->product_id,
                    'variant_id' => $guestItem->variant_id,
                ]);

                $existing->quantity = $this->clampLineQuantity(
                    ($existing->exists ? $existing->quantity : 0) + $guestItem->quantity,
                );
                $existing->price_snapshot = $guestItem->price_snapshot;
                $existing->save();
                $guestItem->delete();
            }

            $guestCart->delete();
        });

        $request->session()->forget(self::SESSION_KEY);

        return $userCart->refresh();
    }

    /**
     * @return array{
     *     itemCount: int,
     *     items: list<array<string, mixed>>,
     *     subtotal: float,
     *     formattedSubtotal: string
     * }
     */
    public function payload(Request $request): array
    {
        return once(fn (): array => $this->buildPayload($request));
    }

    /**
     * @return array{
     *     itemCount: int,
     *     items: list<array<string, mixed>>,
     *     subtotal: float,
     *     formattedSubtotal: string
     * }
     */
    private function buildPayload(Request $request): array
    {
        $cart = $this->activeCart($request);
        $cart->load(['items.product.categories:id,name,slug', 'items.product.media']);

        $items = [];
        $subtotal = 0.0;

        foreach ($cart->items as $item) {
            $product = $item->product;

            if ($product === null || ! $product->status) {
                continue;
            }

            $unit = BranchProductPriceDisplay::catalogEffectiveAmount($product);
            $compareAt = BranchProductPriceDisplay::forCatalogProduct($product)['compare_at'];
            $compareAt = $compareAt !== null && $compareAt > $unit ? $compareAt : null;
            $line = $unit * $item->quantity;
            $subtotal += $line;
            $category = $product->categories->first();

            $items[] = [
                'id' => $item->id,
                'productId' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $category?->name ?? 'Catalog',
                'description' => $product->packSummary() ?? '',
                'image' => $product->imageUrl(MediaUrl::DEFAULT_FALLBACK),
                'unitPrice' => $unit,
                'formattedUnitPrice' => '$'.number_format($unit, 2),
                'compareAtUnitPrice' => $compareAt,
                'formattedCompareAtUnitPrice' => $compareAt === null ? null : '$'.number_format($compareAt, 2),
                'quantity' => $item->quantity,
                'maxQuantity' => self::MAX_LINE_QUANTITY,
                'lineTotal' => $line,
                'formattedLineTotal' => '$'.number_format($line, 2),
            ];
        }

        return [
            'itemCount' => array_sum(array_column($items, 'quantity')),
            'items' => $items,
            'subtotal' => $subtotal,
            'formattedSubtotal' => '$'.number_format($subtotal, 2),
        ];
    }

    private function clampLineQuantity(int $quantity): int
    {
        return min(self::MAX_LINE_QUANTITY, $quantity);
    }
}
