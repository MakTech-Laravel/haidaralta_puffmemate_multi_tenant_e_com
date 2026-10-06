export type DriverPlaceOrderProduct = {
    id: string;
    name: string;
    description: string;
    category: string;
    priceLabel: string;
    price: number;
    stock: number;
    image: string;
};

export type DriverPlaceOrderPaymentMethod = {
    id: string;
    label: string;
    description?: string | null;
};

export type DriverPlaceOrderCatalog = {
    products: DriverPlaceOrderProduct[];
    paymentMethods: DriverPlaceOrderPaymentMethod[];
    canSell: boolean;
};

export type ResolvedPlaceOrderCustomer = {
    id: number;
    name: string;
    phone: string;
    registration_status: string;
};
