<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Http\Requests\Storefront\QuoteShippingRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Customer\CustomerAccountService;
use App\Services\Storefront\CartService;
use App\Services\Storefront\CheckoutService;
use App\Services\Storefront\CouponService;
use App\Support\Storefront\PackCheckoutSession;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
        private readonly CouponService $coupons,
        private readonly PaymentGatewaySettingsService $payments,
        private readonly CustomerAccountService $accounts,
    ) {}

    public function index(): Response|RedirectResponse
    {
        $packPayload = $this->checkout->packPayload(request());

        if ($packPayload !== null) {
            request()->session()->forget('storefront.checkout_coupon');

            $user = $this->carts->customer() ?? request()->user('customer');

            return Inertia::render('storefront/checkout', [
                ...$packPayload,
                'savedAddress' => $user !== null ? $this->accounts->deliveryAddress($user) : null,
                'deliveryMethods' => $this->checkout->deliveryMethods(),
                'paymentMethods' => $this->payments->enabledMethods(),
                'appliedCoupon' => null,
                'shippingQuote' => request()->session()->get('storefront.checkout_shipping'),
            ]);
        }

        $cart = $this->carts->payload(request());

        if ($cart['items'] === []) {
            return redirect()->route('cart');
        }

        $user = $this->carts->customer() ?? request()->user('customer');

        return Inertia::render('storefront/checkout', [
            ...$cart,
            'checkoutMode' => 'cart',
            'couponsAllowed' => true,
            'savedAddress' => $user !== null ? $this->accounts->deliveryAddress($user) : null,
            'deliveryMethods' => $this->checkout->deliveryMethods(),
            'paymentMethods' => $this->payments->enabledMethods(),
            'appliedCoupon' => $this->freshAppliedCoupon($cart, $user),
            'shippingQuote' => request()->session()->get('storefront.checkout_shipping'),
        ]);
    }

    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse
    {
        if ($this->checkout->isPackCheckout($request)) {
            throw ValidationException::withMessages([
                'coupon' => 'Coupons cannot be applied to Packs & Saves orders.',
            ]);
        }

        $cart = $this->carts->payload($request);
        $quoted = $this->coupons->quote(
            $request->validated('coupon'),
            (float) $cart['subtotal'],
            $this->carts->customer($request),
        );

        $applied = [
            'code' => $quoted['coupon']->code,
            'discount' => $quoted['discount'],
            'formattedDiscount' => '-$'.number_format($quoted['discount'], 2),
        ];

        $request->session()->put('storefront.checkout_coupon', $applied);

        return back();
    }

    public function removeCoupon(): RedirectResponse
    {
        request()->session()->forget('storefront.checkout_coupon');

        return back();
    }

    public function quoteShipping(QuoteShippingRequest $request): JsonResponse
    {
        try {
            $quote = $this->checkout->quoteShipping(
                $request,
                (float) $request->validated('latitude'),
                (float) $request->validated('longitude'),
            );
        } catch (ValidationException $exception) {
            $request->session()->forget('storefront.checkout_shipping');

            throw $exception;
        }

        $payload = [
            ...$quote,
            'latitude' => (string) $request->validated('latitude'),
            'longitude' => (string) $request->validated('longitude'),
        ];

        $request->session()->put('storefront.checkout_shipping', $payload);

        return response()->json($payload);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $user = $request->user('customer');

        if ($user === null || ! $user->isCustomer()) {
            return redirect()->guest(route('login'));
        }

        $order = $this->checkout->place($request, $user, $request->validated());

        $request->session()->forget('storefront.checkout_coupon');
        $request->session()->forget('storefront.checkout_shipping');
        PackCheckoutSession::forget($request);

        Toast::success('Order placed.');

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Order $order): Response
    {
        $user = request()->user('customer');
        abort_unless(
            $user !== null && $user->isCustomer() && $order->customer_id === $user->id,
            404,
        );

        return Inertia::render('storefront/checkout-confirmation', [
            'order' => $this->checkout->confirmation($order),
        ]);
    }

    /**
     * Re-quote the session coupon against the current cart product subtotal.
     *
     * @param  array<string, mixed>  $cart
     * @return array{code: string, discount: float, formattedDiscount: string}|null
     */
    private function freshAppliedCoupon(array $cart, ?User $user): ?array
    {
        $session = request()->session()->get('storefront.checkout_coupon');

        if (! is_array($session) || blank($session['code'] ?? null)) {
            return null;
        }

        try {
            $quoted = $this->coupons->quote(
                (string) $session['code'],
                (float) $cart['subtotal'],
                $user,
            );
        } catch (ValidationException) {
            request()->session()->forget('storefront.checkout_coupon');

            return null;
        }

        $applied = [
            'code' => $quoted['coupon']->code,
            'discount' => $quoted['discount'],
            'formattedDiscount' => '-$'.number_format($quoted['discount'], 2),
        ];

        request()->session()->put('storefront.checkout_coupon', $applied);

        return $applied;
    }
}
