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

export type CustomerOrderDetail = {
    id: string;
    orderNumber: string;
    status: 'shipped' | 'processing' | 'delivered';
    products: OrderTrackProduct[];
    totalLabel: string;
    shippingAddress: OrderTrackShippingAddress;
    driver: OrderTrackDriver;
    timeline: OrderTimelineStep[];
};
