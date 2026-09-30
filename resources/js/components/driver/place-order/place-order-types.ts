export type DriverPlaceOrderCustomer = {
    id: string;
    name: string;
    phone: string;
    address: string;
    locationLabel: string;
};

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
};

export type DriverPlaceOrderCatalog = {
    customers: DriverPlaceOrderCustomer[];
    products: DriverPlaceOrderProduct[];
    paymentMethods: DriverPlaceOrderPaymentMethod[];
};
