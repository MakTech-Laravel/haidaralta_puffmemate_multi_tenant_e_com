export type OrderTrackProduct = {
    name: string;
    image: string;
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
    | 'processing'
    | 'ready'
    | 'assigned'
    | 'picked_up'
    | 'out_for_delivery'
    | 'delivery_failed'
    | 'returning'
    | 'delivered'
    | 'cancelled';

export type CustomerPaymentStatus =
    | 'pending_collection'
    | 'collected'
    | 'verified'
    | 'failed'
    | 'refunded';

export type CustomerOrderDetail = {
    orderNumber: string;
    status: CustomerOrderStatus;
    statusLabel?: string;
    paymentStatus: CustomerPaymentStatus;
    paymentStatusLabel?: string;
    handoverNotice?: string | null;
    canCancel?: boolean;
    date: string;
    products: OrderTrackProduct[];
    subtotalLabel: string;
    discountLabel?: string | null;
    couponCode?: string | null;
    deliveryFeeLabel: string;
    totalLabel: string;
    shippingAddress: OrderTrackShippingAddress;
    driver?: OrderTrackDriver | null;
    timeline: OrderTimelineStep[];
};
