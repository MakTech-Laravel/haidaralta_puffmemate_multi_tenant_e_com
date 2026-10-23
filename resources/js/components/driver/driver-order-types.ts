export type DriverOrderStatus =
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

export type DriverOrderListTab = 'active' | 'delivered';

export type DriverOrderPaymentMethod = 'cod' | 'paid';

export type DriverOrderNextActionKey =
    | 'pick_up'
    | 'start'
    | 'arrive'
    | 'complete'
    | 'return';

export type DriverOrderNextAction = {
    key: DriverOrderNextActionKey;
    label: string;
};

export type DriverAssignedOrder = {
    id: string;
    orderNumber: string;
    status: DriverOrderStatus;
    statusLabel?: string;
    canChat?: boolean;
    nextAction?: DriverOrderNextAction | null;
    customerName: string;
    company?: string | null;
    source?: string;
    sourceLabel?: string;
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
    canChat?: boolean;
    canPickUp?: boolean;
    canStart?: boolean;
    canArrive?: boolean;
    canDeliver?: boolean;
    canFail?: boolean;
    canReturn?: boolean;
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
    subtotalLabel: string;
    discountLabel: string | null;
    couponCode: string | null;
    deliveryFeeLabel: string;
};

export const driverOrderStatusLabel: Record<string, string> = {
    pending: 'Pending',
    placed: 'Placed',
    hold: 'On hold',
    processing: 'Processing',
    ready: 'Ready',
    assigned: 'Assigned',
    picked_up: 'Picked up',
    out_for_delivery: 'On the way',
    arrived: 'Arrived',
    delivery_failed: 'Not delivered',
    returning: 'Returning',
    returned: 'Returned',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
};

export const driverOrderListTabLabel: Record<DriverOrderListTab, string> = {
    active: 'To deliver',
    delivered: 'Delivered today',
};

export function filterDriverAssignedOrders(
    orders: DriverAssignedOrder[],
    tab: DriverOrderListTab,
): DriverAssignedOrder[] {
    return orders.filter((order) =>
        tab === 'active'
            ? isDriverWorkloadOrder(order)
            : order.status === 'delivered',
    );
}

const driverWorkloadStatuses: ReadonlySet<DriverOrderStatus> = new Set([
    'assigned',
    'picked_up',
    'out_for_delivery',
    'arrived',
    'delivery_failed',
    'returning',
]);

export function isDriverWorkloadOrder(order: DriverAssignedOrder): boolean {
    return driverWorkloadStatuses.has(order.status);
}

export function driverOrderTabCounts(
    orders: DriverAssignedOrder[],
): Record<DriverOrderListTab, number> {
    return {
        active: filterDriverAssignedOrders(orders, 'active').length,
        delivered: filterDriverAssignedOrders(orders, 'delivered').length,
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

export type DriverEarningsPeriod = 'today' | '7d' | '30d';

export type DriverEarningsPerformance = {
    previousTotalLabel: string;
    changePercent: number | null;
    changeAmountLabel: string;
    direction: 'up' | 'down' | 'flat' | 'new';
    comparisonLabel: string;
};

export type DriverEarnings = {
    period: DriverEarningsPeriod;
    periodLabel: string;
    totalLabel: string;
    drops: number;
    tipsLabel: string;
    perStopLabel: string;
    previousTotalLabel: string;
    codHeldLabel: string;
    deliveries: number;
    performance: DriverEarningsPerformance;
    deliveriesPerformance: DriverEarningsPerformance;
    days: DriverEarningsDay[];
};

export type DriverDashboardSummary = {
    activeCount: number;
    deliveredCount: number;
    assignedCount: number;
    nextStop: DriverAssignedOrder | null;
    activeOrders: DriverAssignedOrder[];
};
