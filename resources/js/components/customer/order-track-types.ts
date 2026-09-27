export type OrderTrackProduct = {
    name: string;
    quantityLabel: string;
    priceLabel: string;
};

export type OrderTrackShippingAddress = {
    name: string;
    lines: string[];
    mobile: string;
};

export type OrderTrackDriver = {
    name: string;
    truckNumber: string;
    phone: string;
};

export type OrderTimelineState = 'completed' | 'current' | 'upcoming';

export type OrderTimelineStep = {
    key: string;
    label: string;
    state: OrderTimelineState;
    detail: string;
};

export type CustomerOrderStatus =
    | 'pending'
    | 'confirmed'
    | 'processing'
    | 'partially_delivered'
    | 'delivered'
    | 'cancelled';

export type CustomerPaymentStatus = 'unpaid' | 'paid' | 'refunded';

export type CustomerOrderDetail = {
    id: string;
    orderNumber: string;
    status: CustomerOrderStatus;
    paymentStatus: CustomerPaymentStatus;
    date: string;
    products: OrderTrackProduct[];
    subtotalLabel: string;
    deliveryFeeLabel: string;
    totalLabel: string;
    shippingAddress: OrderTrackShippingAddress;
    driver?: OrderTrackDriver | null;
    timeline: OrderTimelineStep[];
};
