import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    CheckoutForm,
    type CheckoutDetails,
} from '@/components/storefront/checkout/checkout-form';
import { CheckoutOrderSummary } from '@/components/storefront/checkout/checkout-order-summary';
import type {
    StorefrontAddress,
    StorefrontCartItem,
    StorefrontDeliveryMethod,
    StorefrontPaymentMethod,
    StorefrontTimeSlot,
} from '@/types/storefront';

type AppliedCoupon = {
    code: string;
    discount: number;
    formattedDiscount: string;
};

type CheckoutPageProps = {
    itemCount: number;
    items: StorefrontCartItem[];
    subtotal: number;
    addresses: StorefrontAddress[];
    deliveryMethods: StorefrontDeliveryMethod[];
    paymentMethods: StorefrontPaymentMethod[];
    timeSlots: StorefrontTimeSlot[];
    defaultAddressId: number | null;
    appliedCoupon?: AppliedCoupon | null;
};

export default function StorefrontCheckout({
    itemCount,
    items,
    subtotal,
    addresses,
    deliveryMethods,
    paymentMethods,
    timeSlots,
    defaultAddressId,
    appliedCoupon = null,
}: CheckoutPageProps) {
    const page = usePage<{ errors?: Record<string, string> }>();
    const defaultAddress = addresses.find((row) => row.id === defaultAddressId);
    const [firstName, lastName] = (defaultAddress?.receiver_name ?? '').split(
        / (.+)/,
    );

    const form = useForm({
        first_name: firstName ?? '',
        last_name: lastName ?? '',
        mobile: defaultAddress?.phone ?? '',
        address: defaultAddress?.address ?? '',
        city: defaultAddress?.city ?? '',
        postal_code: defaultAddress?.postal_code ?? '',
        latitude: defaultAddress?.latitude ?? '',
        longitude: defaultAddress?.longitude ?? '',
        address_id: defaultAddressId ? String(defaultAddressId) : '',
        save_as_default: false,
        delivery_method: deliveryMethods[0]?.id ?? '',
        delivery_date: '',
        time_slot_id: timeSlots[0]?.id ?? '',
        payment_method: paymentMethods[0]?.id ?? '',
        coupon: appliedCoupon?.code ?? '',
        terms: false,
    });

    const [couponCode, setCouponCode] = useState(appliedCoupon?.code ?? '');

    const details: CheckoutDetails = {
        firstName: form.data.first_name,
        lastName: form.data.last_name,
        mobile: form.data.mobile,
        address: form.data.address,
        city: form.data.city,
        postalCode: form.data.postal_code,
        latitude: form.data.latitude ?? '',
        longitude: form.data.longitude ?? '',
        addressId: form.data.address_id,
        saveAsDefault: form.data.save_as_default,
        deliveryMethodId: form.data.delivery_method,
        deliveryDate: form.data.delivery_date,
        timeSlotId: form.data.time_slot_id,
        paymentMethodId: form.data.payment_method,
    };

    const selectedMethod = useMemo(
        () =>
            deliveryMethods.find(
                (method) => method.id === form.data.delivery_method,
            ),
        [deliveryMethods, form.data.delivery_method],
    );

    const onDetailChange = <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => {
        const map: Record<keyof CheckoutDetails, keyof typeof form.data> = {
            firstName: 'first_name',
            lastName: 'last_name',
            mobile: 'mobile',
            address: 'address',
            city: 'city',
            postalCode: 'postal_code',
            latitude: 'latitude',
            longitude: 'longitude',
            addressId: 'address_id',
            saveAsDefault: 'save_as_default',
            deliveryMethodId: 'delivery_method',
            deliveryDate: 'delivery_date',
            timeSlotId: 'time_slot_id',
            paymentMethodId: 'payment_method',
        };

        form.setData(map[key], value as never);
    };

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
                            form.setData('coupon', couponCode);
                            form.post('/checkout');
                        }}
                    >
                        <CheckoutForm
                            details={details}
                            addresses={addresses}
                            deliveryMethods={deliveryMethods}
                            paymentMethods={paymentMethods}
                            timeSlots={timeSlots}
                            onChange={onDetailChange}
                        />
                        <CheckoutOrderSummary
                            items={items}
                            itemCount={itemCount}
                            subtotal={subtotal}
                            deliveryFee={selectedMethod?.fee ?? 0}
                            deliveryLabel={
                                selectedMethod?.formattedFee ?? 'FREE'
                            }
                            coupon={couponCode}
                            appliedCoupon={appliedCoupon}
                            couponError={page.props.errors?.coupon}
                            termsAccepted={form.data.terms}
                            processing={form.processing}
                            onCouponChange={setCouponCode}
                            onApplyCoupon={() =>
                                router.post(
                                    '/checkout/coupon',
                                    { coupon: couponCode },
                                    { preserveScroll: true },
                                )
                            }
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
