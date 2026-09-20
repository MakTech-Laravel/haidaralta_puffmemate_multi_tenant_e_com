export type DriverOrderStatus = 'out_for_delivery' | 'arrived' | 'delivered';

export type DriverAssignedOrder = {
    id: string;
    orderNumber: string;
    status: DriverOrderStatus;
    customerName: string;
    addressSummary: string;
    windowLabel: string;
    productCount: number;
    totalLabel: string;
};

export type DriverOrderDetail = DriverAssignedOrder & {
    statusLabel: string;
    phone: string;
    notes: string | null;
    demoOtpHint: string;
    shippingAddress: {
        line1: string;
        line2: string | null;
        city: string;
        region: string;
        postalCode: string;
        country: string;
    };
    products: Array<{
        name: string;
        quantityLabel: string;
        priceLabel: string;
    }>;
};

export const driverOrderStatusLabel: Record<DriverOrderStatus, string> = {
    out_for_delivery: 'Out for delivery',
    arrived: 'Arrived',
    delivered: 'Delivered',
};
