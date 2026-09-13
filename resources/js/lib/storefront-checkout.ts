import { formatMoney } from '@/lib/storefront-cart';

export type CheckoutItem = {
    id: string;
    name: string;
    image: string;
    quantity: number;
    unitPrice: number;
};

export type CheckoutSummary = {
    subtotal: number;
    deliveryLabel: string;
    deliveryAmount: number | null;
    total: number;
};

export type CheckoutDeliveryMethod = {
    id: string;
    title: string;
    description: string;
    meta: string;
};

export type CheckoutPaymentMethod = {
    id: string;
    title: string;
    description: string;
    badge: string;
};

export type CheckoutTimeSlot = {
    id: string;
    label: string;
};

export type CheckoutDetails = {
    firstName: string;
    lastName: string;
    mobile: string;
    location: string;
    deliveryMethodId: string;
    deliveryDate: string;
    timeSlotId: string;
    paymentMethodId: string;
};

export { formatMoney };
