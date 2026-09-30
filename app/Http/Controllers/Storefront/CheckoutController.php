<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Http\Requests\Storefront\QuoteShippingRequest;
use App\Models\Order;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Storefront\CartService;
use App\Services\Storefront\CheckoutService;
use App\Services\Storefront\CouponService;
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
    ) {}

    public function index(): Response|RedirectResponse
    {
        $cart = $this->carts->payload(request());

        if ($cart['items'] === []) {
            return redirect()->route('cart');
        }

        $user = $this->carts->customer() ?? request()->user('customer');
        $addresses = $user !== null ? $this->checkout->addressesFor($user) : [];

        return Inertia::render('storefront/checkout', [
            ...$cart,
            'addresses' => $addresses,
            'deliveryMethods' => $this->checkout->deliveryMethods(),
            'paymentMethods' => $this->payments->enabledMethods(),
            'defaultAddressId' => collect($addresses)->firstWhere('is_default', true)['id'] ?? null,
            'appliedCoupon' => request()->session()->get('storefront.checkout_coupon'),
            'shippingQuote' => request()->session()->get('storefront.checkout_shipping'),
        ]);
    }

    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse
    {
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

        return back()->with(['appliedCoupon' => $applied]);
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

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $order = $this->checkout->place($request, $user, $request->validated());

        $request->session()->forget('storefront.checkout_coupon');
        $request->session()->forget('storefront.checkout_shipping');

        Toast::success('Order placed.');

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Order $order): Response
    {
        $user = request()->user('customer');
        abort_unless($user !== null && $order->customer_id === $user->id, 404);

        return Inertia::render('storefront/checkout-confirmation', [
            'order' => $this->checkout->confirmation($order),
        ]);
    }
}
