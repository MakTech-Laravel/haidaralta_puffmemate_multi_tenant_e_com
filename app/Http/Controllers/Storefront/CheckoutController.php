<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Models\Order;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Storefront\CartService;
use App\Services\Storefront\CheckoutService;
use App\Services\Storefront\CouponService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
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
            'timeSlots' => $this->checkout->timeSlots(),
            'paymentMethods' => $this->payments->enabledMethods(),
            'defaultAddressId' => collect($addresses)->firstWhere('is_default', true)['id'] ?? null,
            'appliedCoupon' => request()->session()->get('storefront.checkout_coupon'),
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

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $user = $request->user('customer');

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $order = $this->checkout->place($request, $user, $request->validated());

        $request->session()->forget('storefront.checkout_coupon');

        Toast::success('Order placed.');

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Order $order): Response
    {
        $user = request()->user('customer');
        abort_unless($user !== null && $order->customer_id === $user->id, 404);

        $order->load('items');

        return Inertia::render('storefront/checkout-confirmation', [
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'total' => '$'.number_format((float) $order->total_amount, 2),
                'paymentMethod' => $order->payment_method,
                'itemCount' => $order->items->sum('quantity'),
            ],
        ]);
    }
}
