export type DriverOrderStatus = 'out_for_delivery' | 'arrived' | 'delivered';

export type DriverOrderStatusFilter = 'all' | 'active' | DriverOrderStatus;

export type DriverOrderPaymentMethod = 'cod' | 'paid';

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
    phone: string;
    notes: string | null;
    unitLabel: string | null;
    productPreview: string[];
    paymentMethod: DriverOrderPaymentMethod;
    etaLabel: string | null;
    completedLabel: string | null;
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

export const driverOrderStatusFilterLabel: Record<
    DriverOrderStatusFilter,
    string
> = {
    all: 'All',
    active: 'Active',
    out_for_delivery: 'On the way',
    arrived: 'Arrived',
    delivered: 'Delivered',
};

export function filterDriverAssignedOrders(
    orders: DriverAssignedOrder[],
    filter: DriverOrderStatusFilter,
): DriverAssignedOrder[] {
    if (filter === 'all') {
        return orders;
    }

    if (filter === 'active') {
        return orders.filter((order) => order.status !== 'delivered');
    }

    return orders.filter((order) => order.status === filter);
}

export function driverOrderFilterCounts(
    orders: DriverAssignedOrder[],
): Record<DriverOrderStatusFilter, number> {
    return {
        all: orders.length,
        active: orders.filter((order) => order.status !== 'delivered').length,
        out_for_delivery: orders.filter(
            (order) => order.status === 'out_for_delivery',
        ).length,
        arrived: orders.filter((order) => order.status === 'arrived').length,
        delivered: orders.filter((order) => order.status === 'delivered')
            .length,
    };
}

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

export function searchDriverAssignedOrders(
    orders: DriverAssignedOrder[],
    query: string,
): DriverAssignedOrder[] {
    const needle = query.trim().toLowerCase();

    if (needle === '') {
        return orders;
    }

    return orders.filter((order) => {
        const haystack = [
            order.orderNumber,
            order.id,
            order.customerName,
            order.addressSummary,
            order.areaLabel,
            order.phone,
            order.unitLabel,
            order.notes,
            ...order.productPreview,
        ]
            .filter((value): value is string => Boolean(value))
            .join(' ')
            .toLowerCase();

        return haystack.includes(needle);
    });
}

export function groupDriverAssignedOrdersByCompletedLabel(
    orders: DriverAssignedOrder[],
): Array<{ label: string; orders: DriverAssignedOrder[] }> {
    const groups: Array<{ label: string; orders: DriverAssignedOrder[] }> = [];

    for (const order of orders) {
        const label = order.completedLabel ?? 'Earlier';
        const last = groups.at(-1);

        if (last && last.label === label) {
            last.orders.push(order);
            continue;
        }

        groups.push({ label, orders: [order] });
    }

    return groups;
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

export type DriverEarningEntry = {
    id: string;
    customerName: string;
    orderNumber: string;
    areaLabel: string;
    amountLabel: string;
    tipLabel: string;
    paymentMethod: DriverOrderPaymentMethod;
};

export type DriverEarningsDay = {
    label: string;
    totalLabel: string;
    entries: DriverEarningEntry[];
};

export type DriverEarnings = {
    todayLabel: string;
    weekLabel: string;
    tipsLabel: string;
    perStopLabel: string;
    payoutLabel: string;
    payoutStatus: string;
    codHeldLabel: string;
    dropsToday: number;
    dropsWeek: number;
    days: DriverEarningsDay[];
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
