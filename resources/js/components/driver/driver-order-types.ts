export type DriverOrderStatus =
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

export type DriverOrderDetail = DriverAssignedOrder & {
    statusLabel: string;
    phone: string;
    notes: string | null;
    demoOtpHint: string;
    canPickUp?: boolean;
    canStart?: boolean;
    canDeliver?: boolean;
    canFail?: boolean;
    failureReasons?: Array<{ value: string; label: string }>;
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

export const driverOrderStatusLabel: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    ready: 'Ready',
    assigned: 'Assigned',
    picked_up: 'Picked up',
    out_for_delivery: 'Out for delivery',
    delivery_failed: 'Delivery failed',
    returning: 'Returning',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
};

export const driverOrderStatusFilterLabel: Record<
    DriverOrderStatusFilter,
    string
> = {
    all: 'All',
    active: 'Active',
    pending: 'Pending',
    processing: 'Processing',
    ready: 'Ready',
    assigned: 'Assigned',
    picked_up: 'Picked up',
    out_for_delivery: 'On the way',
    delivery_failed: 'Failed',
    returning: 'Returning',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
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
    const count = (status: DriverOrderStatus): number =>
        orders.filter((order) => order.status === status).length;

    return {
        all: orders.length,
        active: orders.filter((order) => order.status !== 'delivered').length,
        pending: count('pending'),
        processing: count('processing'),
        ready: count('ready'),
        assigned: count('assigned'),
        picked_up: count('picked_up'),
        out_for_delivery: count('out_for_delivery'),
        delivery_failed: count('delivery_failed'),
        returning: count('returning'),
        delivered: count('delivered'),
        cancelled: count('cancelled'),
    };
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

export function driverMapsQuery(place: string): string {
    return `${place}, NSW, Australia`;
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
    nextStop: DriverAssignedOrder | null;
    activeOrders: DriverAssignedOrder[];
};
