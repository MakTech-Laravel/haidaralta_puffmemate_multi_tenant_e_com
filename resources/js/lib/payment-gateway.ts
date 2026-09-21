import { usePage } from '@inertiajs/react';

export type PaymentGatewayShared = {
    id: string;
    label: string;
    title?: string;
    description?: string;
    badge?: string;
};

/**
 * Resolve a payment method key (cod|pos|payment_link) to its configured label.
 */
export function paymentGatewayLabel(
    key: string | null | undefined,
    gateways: PaymentGatewayShared[] | undefined,
    fallback?: string,
): string {
    if (!key) {
        return fallback ?? '';
    }

    const match = gateways?.find((gateway) => gateway.id === key);

    return match?.label ?? match?.title ?? fallback ?? key;
}

export function usePaymentGatewayLabel(
    key: string | null | undefined,
    fallback?: string,
): string {
    const { paymentGateways } = usePage<{
        paymentGateways?: PaymentGatewayShared[];
    }>().props;

    return paymentGatewayLabel(key, paymentGateways, fallback);
}
