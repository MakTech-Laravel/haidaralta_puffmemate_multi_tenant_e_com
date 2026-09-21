import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { CheckoutForm } from '@/components/storefront/checkout/checkout-form';
import { CheckoutOrderSummary } from '@/components/storefront/checkout/checkout-order-summary';
import type {
    CheckoutDeliveryMethod,
    CheckoutDetails,
    CheckoutItem,
    CheckoutPaymentMethod,
    CheckoutSummary,
    CheckoutTimeSlot,
} from '@/lib/storefront-checkout';

type CheckoutPageProps = {
    itemCount: number;
    items: CheckoutItem[];
    summary: CheckoutSummary;
    deliveryMethods: CheckoutDeliveryMethod[];
    paymentMethods: CheckoutPaymentMethod[];
    timeSlots: CheckoutTimeSlot[];
    trustBadges: string[];
    deliveryAlert: string;
    legalNote: string;
};

export default function StorefrontCheckout({
    itemCount,
    items,
    summary,
    deliveryMethods,
    paymentMethods,
    timeSlots,
    trustBadges,
    deliveryAlert,
    legalNote,
}: CheckoutPageProps) {
    const [details, setDetails] = useState<CheckoutDetails>({
        firstName: '',
        lastName: '',
        mobile: '',
        location: '',
        deliveryMethodId: deliveryMethods[0]?.id ?? 'same-day',
        deliveryDate: '',
        timeSlotId: timeSlots[0]?.id ?? '',
        paymentMethodId: paymentMethods[0]?.id ?? 'cod',
    });
    const [coupon, setCoupon] = useState('');
    const [termsAccepted, setTermsAccepted] = useState(false);

    const onDetailChange = <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => {
        setDetails((current) => ({ ...current, [key]: value }));
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
                        <p className="text-muted-foreground mt-2 text-base font-normal sm:text-base">
                            Everything you need, in one simple checkout.
                        </p>
                    </div>

                    <div className="mt-8 grid items-start gap-6 lg:grid-cols-[1fr_22rem] lg:gap-8">
                        <CheckoutForm
                            details={details}
                            deliveryMethods={deliveryMethods}
                            paymentMethods={paymentMethods}
                            timeSlots={timeSlots}
                            deliveryAlert={deliveryAlert}
                            onChange={onDetailChange}
                        />
                        <CheckoutOrderSummary
                            items={items}
                            itemCount={itemCount}
                            summary={summary}
                            trustBadges={trustBadges}
                            legalNote={legalNote}
                            coupon={coupon}
                            termsAccepted={termsAccepted}
                            onCouponChange={setCoupon}
                            onTermsChange={setTermsAccepted}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
