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
    actorName: string | null;
    detail: string | null;
    comment: string | null;
};

export type CustomerOrderStatus =
    | 'pending'
    | 'placed'
    | 'hold'
    | 'processing'
    | 'ready'
    | 'assigned'
    | 'picked_up'
    | 'out_for_delivery'
    | 'arrived'
    | 'delivery_failed'
    | 'returning'
    | 'returned'
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
    source?: string;
    sourceLabel?: string;
    wholesaleRequestId?: number | null;
    deliveryCode?: string | null;
    canResendCode?: boolean;
    canCancel?: boolean;
    canChat?: boolean;
    canRequestReturn?: boolean;
    returnRequest?: {
        id: number;
        type: string;
        typeLabel: string;
        reason: string;
        note: string | null;
        status: string;
        statusLabel: string;
    } | null;
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
