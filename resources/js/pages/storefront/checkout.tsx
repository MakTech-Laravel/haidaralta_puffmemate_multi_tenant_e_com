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

type ShippingQuote = {
    fee: number;
    formattedFee: string;
    latitude: string;
    longitude: string;
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
};

function sameCoordinate(left: string, right: string): boolean {
    const a = Number(left);
    const b = Number(right);

    return Number.isFinite(a) && Number.isFinite(b) && Math.abs(a - b) < 0.000001;
}

function filled(value: string): boolean {
    return value.trim() !== '';
}

function checkoutCanPlaceOrder(
    details: CheckoutDetails,
    methods: StorefrontDeliveryMethod[],
    termsAccepted: boolean,
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

    if (method?.requiresSlot) {
        return filled(details.timeSlotId);
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
}: CheckoutPageProps) {
    const page = usePage<{ errors?: Record<string, string> }>();
    const defaultMethod = deliveryMethods[0];
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
        time_slot_id: defaultMethod?.requiresSlot
            ? (defaultMethod.timeSlots[0]?.id ?? '')
            : '',
        payment_method: paymentMethods[0]?.id ?? '',
        coupon: appliedCoupon?.code ?? '',
        terms: false,
    });

    const [couponCode, setCouponCode] = useState(appliedCoupon?.code ?? '');
    const [quote, setQuote] = useState<ShippingQuote>(shippingQuote);
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
        { fee: number; formattedFee: string }
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
                setQuote({
                    fee: payload.fee,
                    formattedFee: payload.formattedFee,
                    latitude,
                    longitude,
                });
            })
            .catch(() => {
                setQuote(null);
            });
    }, [
        details.latitude,
        details.longitude,
        details.deliveryMethodId,
        pinKey,
        postQuote,
        transformQuote,
    ]);

    const onDetailChange = <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => {
        if (key === 'deliveryMethodId') {
            const method = deliveryMethods.find((row) => row.id === value);

            form.setData((current) => {
                if (!method?.requiresSlot) {
                    return {
                        ...current,
                        delivery_method: String(value),
                        time_slot_id: '',
                    };
                }

                const slotStillValid = method.timeSlots.some(
                    (slot) => slot.id === current.time_slot_id,
                );

                return {
                    ...current,
                    delivery_method: String(value),
                    time_slot_id: slotStillValid
                        ? current.time_slot_id
                        : (method.timeSlots[0]?.id ?? ''),
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
            timeSlotId: 'time_slot_id',
            paymentMethodId: 'payment_method',
        };

        form.setData(map[key], value as never);
    };

    const applyPin = (
        latitude: string,
        longitude: string,
        address?: string,
    ) => {
        form.setData((current) => ({
            ...current,
            latitude,
            longitude,
            ...(address ? { address } : {}),
        }));
    };

    const errors = {
        ...page.props.errors,
        ...form.errors,
        ...quoteErrors,
    };

    const canPlaceOrder = checkoutCanPlaceOrder(
        details,
        deliveryMethods,
        form.data.terms,
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
                                coupon:
                                    appliedCoupon?.code ??
                                    (couponCode.trim() !== ''
                                        ? couponCode.trim()
                                        : null),
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
                            coupon={couponCode}
                            appliedCoupon={appliedCoupon}
                            couponError={errors.coupon}
                            termsAccepted={form.data.terms}
                            termsError={errors.terms}
                            processing={form.processing}
                            canPlaceOrder={canPlaceOrder}
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
                            onTermsChange={(accepted) =>
                                form.setData('terms', accepted)
                            }
                        />
                    </form>
                </div>
            </div>
        </>
    );
}
