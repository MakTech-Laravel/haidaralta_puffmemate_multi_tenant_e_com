import { Head, router, useForm, useHttp, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    applyCoupon,
    quoteShipping,
    removeCoupon,
    store as storeCheckout,
} from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import {
    CheckoutForm,
    type CheckoutDetails,
} from '@/components/storefront/checkout/checkout-form';
import { CheckoutOrderSummary } from '@/components/storefront/checkout/checkout-order-summary';
import { filterUpcomingDeliverySlots } from '@/lib/delivery-shift-availability';
import type {
    StorefrontCartItem,
    StorefrontDeliveryMethod,
    StorefrontPaymentMethod,
    StorefrontSavedAddress,
} from '@/types/storefront';

type AppliedCoupon = {
    code: string;
    discount: number;
    formattedDiscount: string;
};

type ShortfallLine = {
    productId: number;
    variantId: number | null;
    productPackageId?: number | null;
    productName: string;
    packageLabel?: string | null;
    quantity: number;
    inventoryQuantity?: number;
    localQuantity: number;
    shortfall: number;
    shortfallUnits?: number;
};

type ShippingQuote = {
    fee: number;
    formattedFee: string;
    latitude: string;
    longitude: string;
    requiresLateDelivery: boolean;
    shortfallLines: ShortfallLine[];
} | null;

type CheckoutPageProps = {
    itemCount: number;
    items: StorefrontCartItem[];
    subtotal: number;
    savedAddress: StorefrontSavedAddress | null;
    deliveryMethods: StorefrontDeliveryMethod[];
    paymentMethods: StorefrontPaymentMethod[];
    appliedCoupon?: AppliedCoupon | null;
    shippingQuote?: ShippingQuote;
    couponsAllowed?: boolean;
    checkoutMode?: 'cart' | 'pack';
};

function sameCoordinate(left: string, right: string): boolean {
    const a = Number(left);
    const b = Number(right);

    return Number.isFinite(a) && Number.isFinite(b) && Math.abs(a - b) < 0.000001;
}

function filled(value: string): boolean {
    return value.trim() !== '';
}

/** Delivery method id (server key) for Same day; mirrors DeliveryShiftAvailability::METHOD_SAME_DAY. */
const SAME_DAY_METHOD_ID = 'regular';

/** Mirrors DeliveryShiftAvailability::SAME_DAY_LATE_ITEMS_MESSAGE. */
const SAME_DAY_LATE_ITEMS_MESSAGE =
    "Same day delivery isn't available while some items are on late delivery. Choose Late delivery or remove those items.";

function checkoutCanPlaceOrder(
    details: CheckoutDetails,
    methods: StorefrontDeliveryMethod[],
    termsAccepted: boolean,
    requiresLateDelivery: boolean,
    acceptLateDelivery: boolean,
    sameDayLateConflict: boolean,
): boolean {
    const method = methods.find((row) => row.id === details.deliveryMethodId);
    const pinReady =
        filled(details.latitude) &&
        filled(details.longitude) &&
        Number.isFinite(Number(details.latitude)) &&
        Number.isFinite(Number(details.longitude));

    if (
        !filled(details.name) ||
        !filled(details.mobile) ||
        !filled(details.address) ||
        !filled(details.city) ||
        !pinReady ||
        !filled(details.deliveryMethodId) ||
        !filled(details.paymentMethodId) ||
        !termsAccepted
    ) {
        return false;
    }

    if (requiresLateDelivery && !acceptLateDelivery) {
        return false;
    }

    if (sameDayLateConflict) {
        return false;
    }

    if (method?.requiresDate && !filled(details.deliveryDate)) {
        return false;
    }

    if (method?.requiresSlot) {
        if (!filled(details.timeSlotId)) {
            return false;
        }

        if (!method.requiresDate) {
            const upcoming = filterUpcomingDeliverySlots(method.timeSlots);

            return upcoming.some((slot) => slot.id === details.timeSlotId);
        }

        return true;
    }

    return true;
}

export default function StorefrontCheckout({
    itemCount,
    items,
    subtotal,
    savedAddress,
    deliveryMethods,
    paymentMethods,
    appliedCoupon = null,
    shippingQuote = null,
    couponsAllowed = true,
}: CheckoutPageProps) {
    const page = usePage<{ errors?: Record<string, string> }>();
    const defaultMethod = deliveryMethods[0];
    const defaultSlots = defaultMethod
        ? defaultMethod.requiresDate
            ? defaultMethod.timeSlots
            : filterUpcomingDeliverySlots(defaultMethod.timeSlots)
        : [];
    const form = useForm({
        name: savedAddress?.name ?? '',
        mobile: savedAddress?.mobile ?? '',
        address: savedAddress?.address ?? '',
        city: savedAddress?.city ?? '',
        postal_code: savedAddress?.postal_code ?? '',
        latitude: savedAddress?.latitude ?? '',
        longitude: savedAddress?.longitude ?? '',
        save_as_default: false,
        delivery_method: defaultMethod?.id ?? '',
        delivery_date: defaultMethod?.requiresDate
            ? (defaultMethod.minDate ?? '')
            : '',
        time_slot_id: defaultMethod?.requiresSlot
            ? (defaultSlots[0]?.id ?? '')
            : '',
        payment_method: paymentMethods[0]?.id ?? '',
        coupon: appliedCoupon?.code ?? '',
        accept_late_delivery: false,
        terms: false,
    });

    const [couponCode, setCouponCode] = useState(appliedCoupon?.code ?? '');
    // Server errors the customer has already edited away; reset whenever a new response arrives.
    const [dismissedErrors, setDismissedErrors] = useState<string[]>([]);
    const dismissErrors = (...keys: string[]) =>
        setDismissedErrors((current) => [
            ...current,
            ...keys.filter((key) => !current.includes(key)),
        ]);

    useEffect(() => {
        setDismissedErrors([]);
    }, [page.props.errors]);
    const [quote, setQuote] = useState<ShippingQuote>(
        shippingQuote
            ? {
                  ...shippingQuote,
                  requiresLateDelivery:
                      shippingQuote.requiresLateDelivery ?? false,
                  shortfallLines: shippingQuote.shortfallLines ?? [],
              }
            : null,
    );
    const requestedPin = useRef<string | null>(
        shippingQuote
            ? `${shippingQuote.latitude},${shippingQuote.longitude}`
            : null,
    );
    const {
        transform: transformQuote,
        post: postQuote,
        processing: quoting,
        errors: quoteErrors,
    } = useHttp<
        { latitude: string; longitude: string; delivery_method: string },
        {
            fee: number;
            formattedFee: string;
            requiresLateDelivery?: boolean;
            shortfallLines?: ShortfallLine[];
        }
    >({
        latitude: '',
        longitude: '',
        delivery_method: '',
    });

    const details: CheckoutDetails = {
        name: form.data.name,
        mobile: form.data.mobile,
        address: form.data.address,
        city: form.data.city,
        postalCode: form.data.postal_code,
        latitude: form.data.latitude ?? '',
        longitude: form.data.longitude ?? '',
        saveAsDefault: form.data.save_as_default,
        deliveryMethodId: form.data.delivery_method,
        deliveryDate: form.data.delivery_date,
        timeSlotId: form.data.time_slot_id,
        paymentMethodId: form.data.payment_method,
    };

    const quotedMatchesPin =
        quote !== null &&
        sameCoordinate(quote.latitude, details.latitude) &&
        sameCoordinate(quote.longitude, details.longitude);

    const pinKey =
        details.latitude && details.longitude
            ? `${details.latitude},${details.longitude}:${details.deliveryMethodId}`
            : null;

    useEffect(() => {
        if (pinKey === null || requestedPin.current === pinKey) {
            return;
        }

        const latitude = details.latitude;
        const longitude = details.longitude;
        const lat = Number(latitude);
        const lng = Number(longitude);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        requestedPin.current = pinKey;
        transformQuote(() => ({
            latitude,
            longitude,
            delivery_method: form.data.delivery_method,
        }));

        void postQuote(quoteShipping.url())
            .then((payload) => {
                const requiresLate = Boolean(payload.requiresLateDelivery);
                setQuote({
                    fee: payload.fee,
                    formattedFee: payload.formattedFee,
                    latitude,
                    longitude,
                    requiresLateDelivery: requiresLate,
                    shortfallLines: payload.shortfallLines ?? [],
                });
                if (!requiresLate) {
                    form.setData('accept_late_delivery', false);
                }
            })
            .catch(() => {
                setQuote(null);
                form.setData('accept_late_delivery', false);
            });
    }, [
        details.latitude,
        details.longitude,
        details.deliveryMethodId,
        pinKey,
        postQuote,
        transformQuote,
    ]);

    useEffect(() => {
        const method = deliveryMethods.find(
            (row) => row.id === form.data.delivery_method,
        );

        if (!method?.requiresSlot || method.requiresDate) {
            return;
        }

        const upcoming = filterUpcomingDeliverySlots(method.timeSlots);

        if (
            form.data.time_slot_id !== '' &&
            !upcoming.some((slot) => slot.id === form.data.time_slot_id)
        ) {
            form.setData('time_slot_id', upcoming[0]?.id ?? '');
        }
    }, [
        deliveryMethods,
        form.data.delivery_method,
        form.data.time_slot_id,
    ]);

    const onDetailChange = <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => {
        if (key === 'deliveryMethodId') {
            const method = deliveryMethods.find((row) => row.id === value);

            dismissErrors('delivery_method', 'delivery_date', 'time_slot_id');

            form.setData((current) => {
                if (!method) {
                    return {
                        ...current,
                        delivery_method: String(value),
                        delivery_date: '',
                        time_slot_id: '',
                    };
                }

                const slots = method.requiresDate
                    ? method.timeSlots
                    : filterUpcomingDeliverySlots(method.timeSlots);
                const slotStillValid = slots.some(
                    (slot) => slot.id === current.time_slot_id,
                );

                return {
                    ...current,
                    delivery_method: String(value),
                    delivery_date: method.requiresDate
                        ? (method.minDate ?? '')
                        : '',
                    time_slot_id: method.requiresSlot
                        ? slotStillValid
                            ? current.time_slot_id
                            : (slots[0]?.id ?? '')
                        : '',
                };
            });

            return;
        }

        const map: Record<keyof CheckoutDetails, keyof typeof form.data> = {
            name: 'name',
            mobile: 'mobile',
            address: 'address',
            city: 'city',
            postalCode: 'postal_code',
            latitude: 'latitude',
            longitude: 'longitude',
            saveAsDefault: 'save_as_default',
            deliveryMethodId: 'delivery_method',
            deliveryDate: 'delivery_date',
            timeSlotId: 'time_slot_id',
            paymentMethodId: 'payment_method',
        };

        dismissErrors(map[key]);
        form.setData(map[key], value as never);
    };

    const applyPin = (
        latitude: string,
        longitude: string,
        address?: string,
    ) => {
        dismissErrors('latitude', 'longitude', ...(address ? ['address'] : []));
        form.setData((current) => ({
            ...current,
            latitude,
            longitude,
            ...(address ? { address } : {}),
        }));
    };

    const errors = Object.fromEntries(
        Object.entries({
            ...page.props.errors,
            ...form.errors,
            ...quoteErrors,
        }).filter(([key]) => !dismissedErrors.includes(key)),
    ) as Record<string, string>;

    const requiresLateDelivery = Boolean(
        quotedMatchesPin && quote?.requiresLateDelivery,
    );
    const shortfallLines = quotedMatchesPin
        ? (quote?.shortfallLines ?? [])
        : [];
    const sameDayLateConflict =
        requiresLateDelivery &&
        details.deliveryMethodId === SAME_DAY_METHOD_ID;

    const canPlaceOrder = checkoutCanPlaceOrder(
        details,
        deliveryMethods,
        form.data.terms,
        requiresLateDelivery,
        form.data.accept_late_delivery,
        sameDayLateConflict,
    );

    return (
        <>
            <Head title="Checkout" />

            <div className="flex-1">
                <div className="container mx-auto px-4 py-6 sm:px-6 sm:py-10">
                    <div className="max-w-2xl">
                        <h1 className="text-foreground text-3xl font-semibold tracking-tight sm:text-4xl">
                            Complete your order.
                        </h1>
                        <p className="text-muted-foreground mt-2 text-base font-normal">
                            Everything you need, in one simple checkout.
                        </p>
                    </div>

                    <form
                        className="mt-8 grid items-start gap-6 lg:grid-cols-[1fr_22rem] lg:gap-8"
                        onSubmit={(event) => {
                            event.preventDefault();

                            if (!canPlaceOrder) {
                                return;
                            }

                            form.transform((data) => ({
                                ...data,
                                accept_late_delivery:
                                    requiresLateDelivery &&
                                    data.accept_late_delivery,
                                coupon: couponsAllowed
                                    ? (appliedCoupon?.code ??
                                      (couponCode.trim() !== ''
                                          ? couponCode.trim()
                                          : null))
                                    : null,
                            }));
                            form.post(storeCheckout.url(), {
                                preserveScroll: true,
                            });
                        }}
                    >
                        <CheckoutForm
                            details={details}
                            hasSavedAddress={savedAddress !== null}
                            deliveryMethods={deliveryMethods}
                            paymentMethods={paymentMethods}
                            errors={errors}
                            deliveryMethodError={
                                errors.delivery_method ??
                                (sameDayLateConflict
                                    ? SAME_DAY_LATE_ITEMS_MESSAGE
                                    : undefined)
                            }
                            onChange={onDetailChange}
                            onPinSelect={applyPin}
                        />
                        <CheckoutOrderSummary
                            items={items}
                            itemCount={itemCount}
                            subtotal={subtotal}
                            deliveryFee={
                                quotedMatchesPin ? (quote?.fee ?? 0) : 0
                            }
                            deliveryLabel={
                                quoting
                                    ? 'Calculating…'
                                    : quotedMatchesPin
                                      ? (quote?.formattedFee ??
                                        'Calculated at checkout')
                                      : 'Calculated at checkout'
                            }
                            deliveryIsEstimate={!quotedMatchesPin && !quoting}
                            couponsAllowed={couponsAllowed}
                            coupon={couponCode}
                            appliedCoupon={
                                couponsAllowed ? appliedCoupon : null
                            }
                            couponError={
                                couponsAllowed ? errors.coupon : undefined
                            }
                            termsAccepted={form.data.terms}
                            termsError={errors.terms}
                            processing={form.processing}
                            canPlaceOrder={canPlaceOrder}
                            requiresLateDelivery={requiresLateDelivery}
                            shortfallLines={shortfallLines}
                            acceptLateDelivery={form.data.accept_late_delivery}
                            acceptLateError={errors.accept_late_delivery}
                            sameDayLateConflict={sameDayLateConflict}
                            onAcceptLateDeliveryChange={(accepted) => {
                                dismissErrors('accept_late_delivery');
                                form.setData('accept_late_delivery', accepted);
                            }}
                            onCouponChange={setCouponCode}
                            onApplyCoupon={() =>
                                router.post(
                                    applyCoupon.url(),
                                    { coupon: couponCode },
                                    { preserveScroll: true },
                                )
                            }
                            onRemoveCoupon={() => {
                                router.delete(removeCoupon.url(), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        setCouponCode('');
                                        form.setData('coupon', '');
                                    },
                                });
                            }}
                            onTermsChange={(accepted) => {
                                dismissErrors('terms');
                                form.setData('terms', accepted);
                            }}
                        />
                    </form>
                </div>
            </div>
        </>
    );
}
