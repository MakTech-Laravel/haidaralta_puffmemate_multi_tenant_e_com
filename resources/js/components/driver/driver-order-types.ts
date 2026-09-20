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

export type DriverDashboardHub = {
    online: boolean;
    etaNext: string;
    kmToday: string;
    supportPhone: string;
    branchPhone: string;
    nextCustomerPhone: string | null;
    nextNotes: string | null;
    shift: {
        label: string;
        window: string;
        endsIn: string;
        branch: string;
    };
    earnings: {
        todayLabel: string;
        weekLabel: string;
        tipsLabel: string;
        perStopLabel: string;
    };
    performance: {
        rating: string;
        ratingCount: number;
        onTimePercent: number;
        acceptancePercent: number;
        completionPercent: number;
    };
    vehicle: {
        name: string;
        plate: string;
        fuel: string;
        insuranceUntil: string;
        docsOk: boolean;
    };
    alerts: Array<{ id: string; title: string; body: string }>;
};

export type DriverDashboardSummary = {
    activeCount: number;
    deliveredCount: number;
    assignedCount: number;
    nextStop: DriverAssignedOrder | null;
    activeOrders: DriverAssignedOrder[];
};
