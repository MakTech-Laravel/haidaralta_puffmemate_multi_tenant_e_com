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
import {
    filterUpcomingDeliverySlots,
    SAME_DAY_METHOD_ID,
    sameDayOutOfRadiusMessage,
} from '@/lib/delivery-shift-availability';
import { cart } from '@/routes';
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
    /** Pin is inside the same-day radius of a branch; null when not applicable. */
    sameDayInRadius?: boolean | null;
    sameDayRadiusKm?: number | null;
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

/** Shown when Same day is selected but no delivery shift is left today. */
const SAME_DAY_NO_SHIFTS_MESSAGE =
    "Same day delivery isn't available right now because no delivery shifts are left today. Choose Late delivery to schedule a later day.";

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
    sameDayOutsideRadius: boolean,
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

    if (sameDayLateConflict || sameDayOutsideRadius) {
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

/** Slots the customer can still book for a method (Same day only keeps shifts that have not started). */
function usableSlots(method: StorefrontDeliveryMethod) {
    return method.requiresDate
        ? method.timeSlots
        : filterUpcomingDeliverySlots(method.timeSlots);
}

/**
 * The checkout form is kept in sessionStorage so a customer who leaves (for example to remove a
 * cart item) finds their details again. Terms, coupon and late-delivery opt-in are never kept:
 * they depend on the cart and must be confirmed again.
 */
const CHECKOUT_DRAFT_KEY = 'storefront.checkout.draft';

type CheckoutDraft = {
    name?: unknown;
    mobile?: unknown;
    address?: unknown;
    city?: unknown;
    postal_code?: unknown;
    latitude?: unknown;
    longitude?: unknown;
    delivery_method?: unknown;
    delivery_date?: unknown;
    time_slot_id?: unknown;
    payment_method?: unknown;
};

function readCheckoutDraft(): CheckoutDraft | null {
    try {
        const raw = window.sessionStorage.getItem(CHECKOUT_DRAFT_KEY);
        const parsed: unknown = raw ? JSON.parse(raw) : null;

        return parsed !== null && typeof parsed === 'object'
            ? (parsed as CheckoutDraft)
            : null;
    } catch {
        return null;
    }
}

function writeCheckoutDraft(draft: Record<string, string>): void {
    try {
        window.sessionStorage.setItem(
            CHECKOUT_DRAFT_KEY,
            JSON.stringify(draft),
        );
    } catch {
        // Storage can be unavailable (private mode); the form still works without a draft.
    }
}

function clearCheckoutDraft(): void {
    try {
        window.sessionStorage.removeItem(CHECKOUT_DRAFT_KEY);
    } catch {
        // Nothing to clear.
    }
}

function draftText(value: unknown): string | null {
    return typeof value === 'string' ? value : null;
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
    // Same day stays listed even when no shifts remain, so start on the first method that can be
    // booked; if none can, start on Late rather than the greyed-out Same day card.
    const defaultMethod =
        deliveryMethods.find((method) => usableSlots(method).length > 0) ??
        deliveryMethods.find((method) => method.id !== SAME_DAY_METHOD_ID) ??
        deliveryMethods[0];
    const defaultSlots = defaultMethod ? usableSlots(defaultMethod) : [];
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

    // Restore the draft after hydration (SSR renders the empty form first), then start saving.
    const [draftReady, setDraftReady] = useState(false);

    useEffect(() => {
        const draft = readCheckoutDraft();

        if (draft) {
            form.setData((current) => {
                const method = deliveryMethods.find(
                    (row) => row.id === draft.delivery_method,
                );
                let deliveryMethod = current.delivery_method;
                let deliveryDate = current.delivery_date;
                let timeSlot = current.time_slot_id;

                if (method) {
                    const slots = usableSlots(method);
                    const draftDate = draftText(draft.delivery_date);
                    const draftSlot = draftText(draft.time_slot_id);
                    const dateInRange =
                        draftDate !== null &&
                        method.minDate != null &&
                        method.maxDate != null &&
                        draftDate >= method.minDate &&
                        draftDate <= method.maxDate;

                    deliveryMethod = method.id;
                    deliveryDate = method.requiresDate
                        ? dateInRange
                            ? draftDate
                            : (method.minDate ?? '')
                        : '';
                    timeSlot = slots.some((slot) => slot.id === draftSlot)
                        ? (draftSlot ?? '')
                        : (slots[0]?.id ?? '');
                }

                const payment = draftText(draft.payment_method);

                return {
                    ...current,
                    name: draftText(draft.name) ?? current.name,
                    mobile: draftText(draft.mobile) ?? current.mobile,
                    address: draftText(draft.address) ?? current.address,
                    city: draftText(draft.city) ?? current.city,
                    postal_code:
                        draftText(draft.postal_code) ?? current.postal_code,
                    latitude: draftText(draft.latitude) ?? current.latitude,
                    longitude: draftText(draft.longitude) ?? current.longitude,
                    delivery_method: deliveryMethod,
                    delivery_date: deliveryDate,
                    time_slot_id: timeSlot,
                    payment_method: paymentMethods.some(
                        (row) => row.id === payment,
                    )
                        ? (payment ?? current.payment_method)
                        : current.payment_method,
                };
            });
        }

        setDraftReady(true);
        // Runs once on mount; later edits are saved by the effect below.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (!draftReady) {
            return;
        }

        writeCheckoutDraft({
            name: form.data.name,
            mobile: form.data.mobile,
            address: form.data.address,
            city: form.data.city,
            postal_code: form.data.postal_code,
            latitude: form.data.latitude ?? '',
            longitude: form.data.longitude ?? '',
            delivery_method: form.data.delivery_method,
            delivery_date: form.data.delivery_date,
            time_slot_id: form.data.time_slot_id,
            payment_method: form.data.payment_method,
        });
    }, [
        draftReady,
        form.data.name,
        form.data.mobile,
        form.data.address,
        form.data.city,
        form.data.postal_code,
        form.data.latitude,
        form.data.longitude,
        form.data.delivery_method,
        form.data.delivery_date,
        form.data.time_slot_id,
        form.data.payment_method,
    ]);
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
            sameDayInRadius?: boolean | null;
            sameDayRadiusKm?: number | null;
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
                    sameDayInRadius: payload.sameDayInRadius ?? null,
                    sameDayRadiusKm: payload.sameDayRadiusKm ?? null,
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

    const sameDayInRadius = quotedMatchesPin
        ? (quote?.sameDayInRadius ?? null)
        : null;
    const sameDayOutsideRadius =
        sameDayInRadius === false &&
        details.deliveryMethodId === SAME_DAY_METHOD_ID;
    const selectedDeliveryMethod = deliveryMethods.find(
        (method) => method.id === details.deliveryMethodId,
    );
    const sameDayNoShifts =
        selectedDeliveryMethod?.id === SAME_DAY_METHOD_ID &&
        usableSlots(selectedDeliveryMethod).length === 0;

    const canPlaceOrder = checkoutCanPlaceOrder(
        details,
        deliveryMethods,
        form.data.terms,
        requiresLateDelivery,
        form.data.accept_late_delivery,
        sameDayLateConflict,
        sameDayOutsideRadius,
    );

    return (
        <>
            <Head title="Checkout" />

            <div className="flex-1">
                <div className="bg-muted">
                    <div className="container mx-auto px-4 py-8 sm:px-6 sm:py-12">
                        <div className="max-w-2xl">
                            <h1 className="text-foreground text-4xl font-semibold tracking-tight sm:text-5xl">
                                Complete your order.
                            </h1>
                            <p className="text-muted-foreground mt-2 text-base font-normal sm:text-lg">
                                Everything you need, in one simple checkout.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="container mx-auto px-4 py-6 sm:px-6 sm:py-10">
                    <form
                        className="grid items-start gap-6 lg:grid-cols-[1fr_24rem] lg:gap-8"
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
                                onSuccess: () => clearCheckoutDraft(),
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
                                (sameDayOutsideRadius
                                    ? sameDayOutOfRadiusMessage(
                                          quote?.sameDayRadiusKm,
                                      )
                                    : sameDayNoShifts
                                      ? SAME_DAY_NO_SHIFTS_MESSAGE
                                      : sameDayLateConflict
                                      ? SAME_DAY_LATE_ITEMS_MESSAGE
                                      : undefined)
                            }
                            sameDayInRadius={sameDayInRadius}
                            sameDayRadiusKm={quote?.sameDayRadiusKm ?? null}
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
                            sameDayUnavailable={
                                sameDayOutsideRadius || sameDayNoShifts
                            }
                            cartUrl={cart.url()}
                            onAcceptLateDeliveryChange={(accepted) => {
                                dismissErrors(
                                    'accept_late_delivery',
                                    'delivery_method',
                                    'delivery_date',
                                    'time_slot_id',
                                );

                                if (
                                    accepted &&
                                    details.deliveryMethodId ===
                                        SAME_DAY_METHOD_ID
                                ) {
                                    const lateMethod = deliveryMethods.find(
                                        (method) => method.requiresDate,
                                    );

                                    form.setData((current) => ({
                                        ...current,
                                        accept_late_delivery: true,
                                        delivery_method:
                                            lateMethod?.id ??
                                            current.delivery_method,
                                        delivery_date: '',
                                        time_slot_id: '',
                                    }));

                                    return;
                                }

                                form.setData(
                                    'accept_late_delivery',
                                    accepted,
                                );
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
