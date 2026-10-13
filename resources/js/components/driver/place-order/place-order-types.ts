import type { PaymentMethodOption } from '@/components/orders/payment-method-options';

export type DriverPlaceOrderProduct = {
    id: string;
    name: string;
    description: string;
    sku: string;
    category: string;
    priceLabel: string;
    price: number;
    stock: number;
    image: string;
};

export type DriverPlaceOrderPaymentMethod = PaymentMethodOption;

export type DriverPlaceOrderCatalog = {
    products: DriverPlaceOrderProduct[];
    paymentMethods: DriverPlaceOrderPaymentMethod[];
    canSell: boolean;
};

export type ResolvedPlaceOrderCustomer = {
    id: number;
    name: string;
    phone: string;
    email?: string | null;
    registration_status: string;
};
