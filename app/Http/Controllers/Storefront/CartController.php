<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Storefront\CartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
    ) {}

    public function index(): Response
    {
        return Inertia::render('storefront/cart', $this->carts->payload(request()));
    }

    public function store(AddCartItemRequest $request): RedirectResponse
    {
        $product = Product::query()
            ->where('slug', $request->validated('product'))
            ->firstOrFail();

        try {
            $this->carts->add(
                $request,
                $product,
                (int) ($request->validated('quantity') ?? 1),
                $request->validated('variant_id'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['product' => $exception->getMessage()]);
        }

        if ($request->boolean('count_view')) {
            $product->increment('views');
        }

        return back();
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        try {
            $this->carts->updateQuantity($request, $cartItem, (int) $request->validated('quantity'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back();
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        try {
            $this->carts->remove(request(), $cartItem);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cart' => $exception->getMessage()]);
        }

        return back();
    }
}
