export type DriverOrderStatus = 'out_for_delivery' | 'arrived' | 'delivered';

export type DriverAssignedOrder = {
    id: string;
    orderNumber: string;
    status: DriverOrderStatus;
    customerName: string;
    areaId: string;
    areaLabel: string;
    addressSummary: string;
    windowLabel: string;
    productCount: number;
    totalLabel: string;
};

export type DriverAssignedArea = {
    id: string;
    label: string;
    orderCount: number;
    remainingCount: number;
    deliveredCount: number;
    remaining: DriverAssignedOrder[];
    delivered: DriverAssignedOrder[];
    orders: DriverAssignedOrder[];
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

export function groupDriverAssignedOrdersByArea(
    orders: DriverAssignedOrder[],
): DriverAssignedArea[] {
    const buckets: DriverAssignedArea[] = [];
    const indexById = new Map<string, number>();

    for (const order of orders) {
        let index = indexById.get(order.areaId);

        if (index === undefined) {
            index = buckets.length;
            indexById.set(order.areaId, index);
            buckets.push({
                id: order.areaId,
                label: order.areaLabel,
                orderCount: 0,
                remainingCount: 0,
                deliveredCount: 0,
                remaining: [],
                delivered: [],
                orders: [],
            });
        }

        const area = buckets[index];

        if (!area) {
            continue;
        }

        area.orders.push(order);
        area.orderCount += 1;

        if (order.status === 'delivered') {
            area.delivered.push(order);
            area.deliveredCount += 1;
        } else {
            area.remaining.push(order);
            area.remainingCount += 1;
        }
    }

    return buckets;
}

export function driverAreaMapsQuery(label: string): string {
    return `${label}, NSW, Australia`;
}

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
    areaCount: number;
    nextStop: DriverAssignedOrder | null;
    activeOrders: DriverAssignedOrder[];
    currentArea: DriverAssignedArea | null;
    laterAreas: DriverAssignedArea[];
};
