import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarClock,
    CreditCard,
    History,
    Mail,
    MapPin,
    Package,
    Phone,
    ReceiptText,
    Truck,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import OrderAssignController from '@/actions/App/Http/Controllers/Branch/OrderAssignController';
import OrderAssignHistoryController from '@/actions/App/Http/Controllers/Branch/Reports/OrderAssignHistoryController';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
import StockTransferController from '@/actions/App/Http/Controllers/Branch/StockTransferController';
import {
    HeaderFact,
    SectionCard,
    SummaryRow,
} from '@/components/orders/order-detail-section';
import { OrderHandoverActions } from '@/components/orders/order-handover-actions';
import { OrderReturnRequest } from '@/components/orders/order-return-request';
import { OrderSourceBadge } from '@/components/orders/order-source-badge';
import { OrderStatusProgress } from '@/components/orders/order-status-progress';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatAppDateTime } from '@/lib/datetime';
import {
    orderStatusBadgeClass,
    paymentStatusBadgeClass,
} from '@/lib/order-badges';
import { paymentMethodLabel } from '@/lib/payment-method-label';

const REASON_MAX = 255;

type FulfillmentDetail = {
    id: number;
    fulfillmentNumber: string;
    status: string;
    statusLabel: string;
    actions: { key: string; label: string; status: string }[];
    shippingCostLabel: string;
    distanceKmLabel: string;
    isPaid: boolean;
    drivers: { id: number; name: string }[];
    driverId: number | null;
    driverName: string | null;
    canAssign: boolean;
    canResendCode: boolean;
    canCancel: boolean;
    canRetry: boolean;
    canReschedule: boolean;
    canReturn: boolean;
    canConfirmReturn: boolean;
    canComplete?: boolean;
    canReportUnavailable?: boolean;
    canCompleteWithoutCode?: boolean;
    failureReasons?: { value: string; label: string }[];
    verificationMethods?: { value: string; label: string }[];
    order: {
        id: number;
        orderNumber: string;
        customerName: string;
        customerPhone: string | null;
        customerEmail?: string | null;
        source: string;
        sourceLabel: string;
        notes: string | null;
        placedAt: string | null;
        deliveryDate: string | null;
        timeSlot: string | null;
        paymentMethod: string;
        paymentStatus: string;
        paymentStatusLabel: string;
        subtotalLabel: string;
        discountLabel: string | null;
        couponCode: string | null;
        deliveryFeeLabel: string;
        totalLabel: string;
        returnRequest?: {
            id: number;
            type: string;
            typeLabel: string;
            reason: string;
            note: string | null;
            status: string;
            statusLabel: string;
        } | null;
        shippingAddress: {
            name?: string;
            address?: string;
            city?: string;
            postal_code?: string;
            phone?: string;
            email?: string;
            company?: string | null;
        } | null;
    };
    items: {
        name: string;
        image: string;
        quantity: number;
        unitPriceLabel: string;
        subtotalLabel: string;
    }[];
    timeline: {
        key: string;
        label: string;
        state: 'completed' | 'current' | 'upcoming';
        actorName: string | null;
        detail: string | null;
        comment: string | null;
    }[];
    transfers: {
        id: number;
        status: string;
        statusLabel: string;
        sourceName: string | null;
        destinationName: string | null;
        estimatedCostLabel: string;
        items: { name: string; quantity: number }[];
        canReceive: boolean;
    }[];
};

type Branch = { id: number; name: string; slug: string };

export default function BranchOrderShow({
    branch,
    fulfillment,
}: {
    branch: Branch;
    fulfillment: FulfillmentDetail;
}) {
    const [driverId, setDriverId] = useState(
        fulfillment.driverId ? String(fulfillment.driverId) : '',
    );
    const [assignReason, setAssignReason] = useState('');
    const [pendingAction, setPendingAction] = useState<string | null>(null);
    const processing = pendingAction !== null;
    const isReassign = fulfillment.driverId !== null;
    const needsAssignReason =
        isReassign || fulfillment.status === 'returned';

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Orders', href: `/branch/${branch.slug}/orders` },
            {
                title: fulfillment.fulfillmentNumber,
                href: '#',
            },
        ],
    });

    const address = fulfillment.order.shippingAddress;
    const recipientName = address?.name || fulfillment.order.customerName;
    const recipientPhone = address?.phone || fulfillment.order.customerPhone;
    const recipientEmail =
        address?.email || fulfillment.order.customerEmail || null;
    const cityLine = [address?.city, address?.postal_code]
        .filter(Boolean)
        .join(' ');
    const hasStreetAddress = Boolean(address?.address || cityLine);
    const itemCount = fulfillment.items.reduce(
        (sum, item) => sum + item.quantity,
        0,
    );

    const spinnerFor = (key: string) =>
        pendingAction === key ? <Spinner /> : null;

    const post = (key: string, url: string) => {
        setPendingAction(key);
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setPendingAction(null),
            },
        );
    };

    const updateStatus = (key: string, status: string) => {
        setPendingAction(key);
        router.put(
            OrderController.updateStatus.url([branch.slug, fulfillment.id]),
            { status },
            {
                preserveScroll: true,
                onFinish: () => setPendingAction(null),
            },
        );
    };

    const resendCode = () => {
        setPendingAction('resend');
        router.post(
            OrderController.resendPlacementCode.url([
                branch.slug,
                fulfillment.id,
            ]),
            {},
            {
                preserveScroll: true,
                onError: (errors) =>
                    toast.error(
                        errors.code ?? 'The delivery code could not be sent.',
                    ),
                onFinish: () => setPendingAction(null),
            },
        );
    };

    const hasOrderActions =
        fulfillment.canCancel ||
        fulfillment.canRetry ||
        fulfillment.canReschedule ||
        fulfillment.canReturn ||
        fulfillment.canConfirmReturn ||
        fulfillment.canResendCode;
    const hasActions = fulfillment.actions.length > 0 || hasOrderActions;

    return (
        <>
            <Head title={`Order ${fulfillment.fulfillmentNumber}`} />

            <div className="mx-auto w-full container space-y-6 p-2 md:p-4">
                <header className="border-border/70 bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="from-primary/10 via-background to-background flex flex-wrap items-start justify-between gap-4 bg-linear-to-br px-5 py-5 sm:px-6">
                        <div className="min-w-0 space-y-2">
                            <p className="text-muted-foreground text-xs font-semibold tracking-[0.14em] uppercase">
                                Branch order
                            </p>
                            <h1 className="text-foreground truncate text-2xl font-bold tracking-tight sm:text-3xl">
                                {fulfillment.fulfillmentNumber}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                Order {fulfillment.order.orderNumber}
                            </p>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    className={orderStatusBadgeClass(
                                        fulfillment.status,
                                    )}
                                >
                                    {fulfillment.statusLabel}
                                </Badge>
                                <Badge
                                    variant="outline"
                                    className={paymentStatusBadgeClass(
                                        fulfillment.order.paymentStatus,
                                    )}
                                >
                                    {fulfillment.order.paymentStatusLabel}
                                </Badge>
                                <OrderSourceBadge
                                    source={fulfillment.order.source}
                                    label={fulfillment.order.sourceLabel}
                                />
                            </div>
                        </div>
                        <div className="flex flex-col items-start gap-3 sm:items-end">
                            <div className="text-left sm:text-right">
                                <p className="text-muted-foreground text-xs font-medium">
                                    Order total
                                </p>
                                <p className="text-foreground text-3xl font-bold tracking-tight tabular-nums">
                                    {fulfillment.order.totalLabel}
                                </p>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={OrderAssignHistoryController.index.url(
                                        branch.slug,
                                        {
                                            query: {
                                                filter: {
                                                    order_id:
                                                        fulfillment.fulfillmentNumber,
                                                },
                                            },
                                        },
                                    )}
                                >
                                    <History aria-hidden />
                                    Driver assign history
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="grid gap-4 border-t px-5 py-4 sm:grid-cols-2 sm:px-6 xl:grid-cols-3">
                        <HeaderFact icon={UserRound} label="Customer">
                            <span className="truncate">
                                {fulfillment.order.customerName}
                            </span>
                            {fulfillment.order.customerPhone ? (
                                <span className="text-muted-foreground block truncate font-normal">
                                    <a
                                        href={`tel:${fulfillment.order.customerPhone}`}
                                        className="hover:text-foreground"
                                    >
                                        {fulfillment.order.customerPhone}
                                    </a>
                                </span>
                            ) : null}
                        </HeaderFact>
                        <HeaderFact icon={CalendarClock} label="Placed">
                            {formatAppDateTime(fulfillment.order.placedAt) ||
                                '—'}
                        </HeaderFact>
                        <HeaderFact icon={CreditCard} label="Payment method">
                            {paymentMethodLabel(
                                fulfillment.order.paymentMethod,
                            )}
                        </HeaderFact>
                    </div>
                </header>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <div className="min-w-0 space-y-6">
                        <Card className="border-border/70 gap-0 overflow-hidden py-0">
                            <div className="bg-muted/30 flex flex-wrap items-start justify-between gap-3 border-b px-5 py-4">
                                <div className="min-w-0 space-y-1">
                                    <div className="flex items-center gap-2">
                                        <Package
                                            className="text-muted-foreground size-4 shrink-0"
                                            aria-hidden
                                        />
                                        <h2 className="truncate text-base font-semibold tracking-tight">
                                            {fulfillment.fulfillmentNumber}
                                        </h2>
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        Order {fulfillment.order.orderNumber}
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        className={orderStatusBadgeClass(
                                            fulfillment.status,
                                        )}
                                    >
                                        {fulfillment.statusLabel}
                                    </Badge>
                                    <Badge
                                        variant="outline"
                                        className={paymentStatusBadgeClass(
                                            fulfillment.isPaid
                                                ? 'collected'
                                                : 'pending_collection',
                                        )}
                                    >
                                        {fulfillment.isPaid
                                            ? 'Collected'
                                            : 'Pending collection'}
                                    </Badge>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3">
                                <p className="text-muted-foreground flex min-w-0 items-center gap-2 text-sm">
                                    <Truck
                                        className="size-4 shrink-0"
                                        aria-hidden
                                    />
                                    <span className="truncate">
                                        {fulfillment.driverName ? (
                                            <>
                                                Driver{' '}
                                                <span className="text-foreground font-medium">
                                                    {fulfillment.driverName}
                                                </span>
                                            </>
                                        ) : (
                                            'No driver assigned yet'
                                        )}
                                    </span>
                                </p>
                            </div>

                            <div className="px-5 py-4">
                                <div className="mb-2 flex items-center justify-between gap-3">
                                    <h3 className="text-sm font-semibold">
                                        Items
                                    </h3>
                                    <span className="text-muted-foreground text-xs font-medium tracking-wider uppercase">
                                        {itemCount} item
                                        {itemCount === 1 ? '' : 's'}
                                    </span>
                                </div>
                                <ul className="divide-border max-h-[26rem] divide-y overflow-y-auto">
                                    {fulfillment.items.map((item, index) => (
                                        <li
                                            key={`${item.name}-${index}`}
                                            className="flex items-center gap-3 py-3"
                                        >
                                            <div className="bg-muted size-14 shrink-0 overflow-hidden rounded-lg border">
                                                <StorefrontProductImage
                                                    src={item.image}
                                                    alt={item.name}
                                                />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-foreground line-clamp-2 text-sm font-medium">
                                                    {item.name}
                                                </p>
                                                <p className="text-muted-foreground mt-0.5 text-xs">
                                                    Qty {item.quantity} ·{' '}
                                                    {item.unitPriceLabel}
                                                </p>
                                            </div>
                                            <p className="text-foreground shrink-0 text-sm font-semibold tabular-nums">
                                                {item.subtotalLabel}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                                <div className="border-border mt-1 space-y-1 border-t pt-3 text-sm">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="text-muted-foreground">
                                            Shipping cost
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {fulfillment.shippingCostLabel}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="text-muted-foreground">
                                            Distance
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {fulfillment.distanceKmLabel}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {fulfillment.transfers.length > 0 ? (
                                <div className="space-y-3 border-t px-5 py-4">
                                    <h3 className="text-sm font-semibold">
                                        Stock transfers
                                    </h3>
                                    {fulfillment.transfers.map((transfer) => (
                                        <div
                                            key={transfer.id}
                                            className="bg-muted/20 space-y-3 rounded-lg border p-3"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div className="text-foreground flex min-w-0 items-center gap-1.5 text-sm font-medium">
                                                    <span className="truncate">
                                                        {transfer.sourceName ??
                                                            'Source'}
                                                    </span>
                                                    <ArrowRight
                                                        className="text-muted-foreground size-3.5 shrink-0"
                                                        aria-hidden
                                                    />
                                                    <span className="truncate">
                                                        {transfer.destinationName ??
                                                            'Destination'}
                                                    </span>
                                                </div>
                                                <Badge variant="outline">
                                                    {transfer.statusLabel}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-xs">
                                                Est. cost{' '}
                                                {transfer.estimatedCostLabel}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {transfer.items
                                                    .map(
                                                        (item) =>
                                                            `${item.name} × ${item.quantity}`,
                                                    )
                                                    .join(', ')}
                                            </p>
                                            {transfer.canReceive ? (
                                                <Button
                                                    size="sm"
                                                    disabled={processing}
                                                    onClick={() =>
                                                        post(
                                                            `receive-${transfer.id}`,
                                                            StockTransferController.receive.url(
                                                                [
                                                                    branch.slug,
                                                                    transfer.id,
                                                                ],
                                                            ),
                                                        )
                                                    }
                                                >
                                                    {spinnerFor(
                                                        `receive-${transfer.id}`,
                                                    )}
                                                    Receive
                                                </Button>
                                            ) : null}
                                        </div>
                                    ))}
                                </div>
                            ) : null}

                            <div className="bg-muted/30 space-y-3 border-t px-5 py-4">
                                <h3 className="text-sm font-semibold">
                                    Fulfillment actions
                                </h3>
                                {hasActions ? (
                                    <div className="flex flex-wrap gap-2">
                                        {fulfillment.actions.map((action) => (
                                            <Button
                                                key={action.key}
                                                disabled={processing}
                                                onClick={() =>
                                                    updateStatus(
                                                        action.key,
                                                        action.status,
                                                    )
                                                }
                                            >
                                                {spinnerFor(action.key)}
                                                {action.label}
                                            </Button>
                                        ))}
                                        {fulfillment.canResendCode ? (
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                                onClick={resendCode}
                                            >
                                                {spinnerFor('resend')}
                                                Resend delivery code
                                            </Button>
                                        ) : null}
                                        {fulfillment.canRetry ? (
                                            <Button
                                                disabled={processing}
                                                onClick={() =>
                                                    post(
                                                        'retry',
                                                        OrderController.retryDelivery.url(
                                                            [
                                                                branch.slug,
                                                                fulfillment.id,
                                                            ],
                                                        ),
                                                    )
                                                }
                                            >
                                                {spinnerFor('retry')}
                                                Retry delivery
                                            </Button>
                                        ) : null}
                                        {fulfillment.canReschedule ? (
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() =>
                                                    post(
                                                        'reschedule',
                                                        OrderController.rescheduleDelivery.url(
                                                            [
                                                                branch.slug,
                                                                fulfillment.id,
                                                            ],
                                                        ),
                                                    )
                                                }
                                            >
                                                {spinnerFor('reschedule')}
                                                Reschedule
                                            </Button>
                                        ) : null}
                                        {fulfillment.canReturn ? (
                                            <Button
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() =>
                                                    post(
                                                        'return',
                                                        OrderController.returnDelivery.url(
                                                            [
                                                                branch.slug,
                                                                fulfillment.id,
                                                            ],
                                                        ),
                                                    )
                                                }
                                            >
                                                {spinnerFor('return')}
                                                Start return
                                            </Button>
                                        ) : null}
                                        {fulfillment.canConfirmReturn ? (
                                            <Button
                                                disabled={processing}
                                                onClick={() =>
                                                    post(
                                                        'confirm-return',
                                                        OrderController.confirmReturn.url(
                                                            [
                                                                branch.slug,
                                                                fulfillment.id,
                                                            ],
                                                        ),
                                                    )
                                                }
                                            >
                                                {spinnerFor('confirm-return')}
                                                Confirm return
                                            </Button>
                                        ) : null}
                                        {fulfillment.canCancel ? (
                                            <Button
                                                variant="outline"
                                                className="text-destructive hover:text-destructive"
                                                disabled={processing}
                                                onClick={() =>
                                                    post(
                                                        'cancel',
                                                        OrderController.cancel.url(
                                                            [
                                                                branch.slug,
                                                                fulfillment.id,
                                                            ],
                                                        ),
                                                    )
                                                }
                                            >
                                                {spinnerFor('cancel')}
                                                Cancel order
                                            </Button>
                                        ) : null}
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        No status change is available from here.
                                    </p>
                                )}
                            </div>

                            {fulfillment.canAssign ? (
                                <div className="space-y-3 border-t px-5 py-4">
                                    <h3 className="text-sm font-semibold">
                                        {isReassign
                                            ? 'Change driver'
                                            : 'Assign driver'}
                                    </h3>
                                    <Select
                                        value={driverId}
                                        onValueChange={setDriverId}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Choose a driver" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {fulfillment.drivers.map(
                                                (driver) => (
                                                    <SelectItem
                                                        key={driver.id}
                                                        value={String(
                                                            driver.id,
                                                        )}
                                                    >
                                                        {driver.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {needsAssignReason ? (
                                        <div className="space-y-2">
                                            <Label htmlFor="assign-reason">
                                                Reason for change{' '}
                                                <span className="text-destructive">
                                                    *
                                                </span>
                                            </Label>
                                            <Textarea
                                                id="assign-reason"
                                                value={assignReason}
                                                onChange={(event) =>
                                                    setAssignReason(
                                                        event.target.value.slice(
                                                            0,
                                                            REASON_MAX,
                                                        ),
                                                    )
                                                }
                                                placeholder="Why are you changing the driver?"
                                                rows={3}
                                                maxLength={REASON_MAX}
                                                required
                                                aria-required
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                Required · {assignReason.length}
                                                /{REASON_MAX}
                                            </p>
                                        </div>
                                    ) : null}
                                    <div className="flex flex-wrap gap-2">
                                        {(
                                            [
                                                {
                                                    label: 'Assign',
                                                    advance: undefined,
                                                    key: 'assign',
                                                },
                                                {
                                                    label: 'Assign and picked up',
                                                    advance: 'picked_up',
                                                    key: 'assign-picked-up',
                                                },
                                                {
                                                    label: 'Assign, picked up, and on the way',
                                                    advance: 'out_for_delivery',
                                                    key: 'assign-on-the-way',
                                                },
                                            ] as const
                                        ).map((action) => (
                                            <Button
                                                key={action.key}
                                                variant={
                                                    action.advance
                                                        ? 'outline'
                                                        : 'default'
                                                }
                                                disabled={
                                                    processing ||
                                                    driverId === '' ||
                                                    (needsAssignReason &&
                                                        assignReason.trim() ===
                                                            '')
                                                }
                                                onClick={() => {
                                                    setPendingAction(
                                                        action.key,
                                                    );
                                                    router.post(
                                                        OrderAssignController.store.url(
                                                            [
                                                                branch.slug,
                                                                fulfillment.id,
                                                            ],
                                                        ),
                                                        {
                                                            driver_id:
                                                                Number(
                                                                    driverId,
                                                                ),
                                                            ...(needsAssignReason
                                                                ? {
                                                                      reason: assignReason.trim(),
                                                                  }
                                                                : {}),
                                                            ...(action.advance
                                                                ? {
                                                                      advance:
                                                                          action.advance,
                                                                  }
                                                                : {}),
                                                        },
                                                        {
                                                            preserveScroll:
                                                                true,
                                                            onFinish: () =>
                                                                setPendingAction(
                                                                    null,
                                                                ),
                                                        },
                                                    );
                                                }}
                                            >
                                                {spinnerFor(action.key)}
                                                {action.label}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                        </Card>

                        <OrderHandoverActions
                            orderId={fulfillment.id}
                            deliverForm={OrderController.deliver.form([
                                branch.slug,
                                fulfillment.id,
                            ])}
                            failForm={OrderController.fail.form([
                                branch.slug,
                                fulfillment.id,
                            ])}
                            resendForm={
                                fulfillment.canResendCode
                                    ? OrderController.resendPlacementCode.form([
                                          branch.slug,
                                          fulfillment.id,
                                      ])
                                    : null
                            }
                            withoutCodeForm={OrderController.deliverWithoutCode.form(
                                [branch.slug, fulfillment.id],
                            )}
                            canComplete={fulfillment.canComplete ?? false}
                            canReportUnavailable={
                                fulfillment.canReportUnavailable ?? false
                            }
                            canCompleteWithoutCode={
                                fulfillment.canCompleteWithoutCode ?? false
                            }
                            failureReasons={fulfillment.failureReasons ?? []}
                            verificationMethods={
                                fulfillment.verificationMethods ?? []
                            }
                        />

                        <OrderReturnRequest
                            canRequest={false}
                            request={fulfillment.order.returnRequest ?? null}
                            approveUrl={
                                fulfillment.order.returnRequest
                                    ? OrderController.approveReturn.url([
                                          branch.slug,
                                          fulfillment.id,
                                          fulfillment.order.returnRequest.id,
                                      ])
                                    : undefined
                            }
                            rejectUrl={
                                fulfillment.order.returnRequest
                                    ? OrderController.rejectReturn.url([
                                          branch.slug,
                                          fulfillment.id,
                                          fulfillment.order.returnRequest.id,
                                      ])
                                    : undefined
                            }
                        />

                        <SectionCard icon={History} title="Order progress">
                            <OrderStatusProgress steps={fulfillment.timeline} />
                        </SectionCard>
                    </div>

                    <aside className="min-w-0 space-y-6 lg:sticky lg:top-6">
                        <SectionCard icon={MapPin} title="Delivery details">
                            <div className="space-y-3 text-sm">
                                <div className="space-y-1">
                                    <p className="text-foreground font-semibold">
                                        {recipientName}
                                    </p>
                                    {address?.company ? (
                                        <p className="text-foreground">
                                            {address.company}
                                        </p>
                                    ) : null}
                                    {recipientPhone ? (
                                        <p className="text-muted-foreground flex items-center gap-1.5">
                                            <Phone
                                                className="size-3.5 shrink-0"
                                                aria-hidden
                                            />
                                            <a
                                                href={`tel:${recipientPhone}`}
                                                className="hover:text-foreground"
                                            >
                                                {recipientPhone}
                                            </a>
                                        </p>
                                    ) : null}
                                    {recipientEmail ? (
                                        <p className="text-muted-foreground flex items-center gap-1.5">
                                            <Mail
                                                className="size-3.5 shrink-0"
                                                aria-hidden
                                            />
                                            <a
                                                href={`mailto:${recipientEmail}`}
                                                className="hover:text-foreground truncate"
                                            >
                                                {recipientEmail}
                                            </a>
                                        </p>
                                    ) : null}
                                </div>
                                <div className="bg-muted/40 rounded-lg p-3">
                                    {hasStreetAddress ? (
                                        <>
                                            {address?.address ? (
                                                <p className="text-foreground break-words">
                                                    {address.address}
                                                </p>
                                            ) : null}
                                            {cityLine ? (
                                                <p className="text-muted-foreground">
                                                    {cityLine}
                                                </p>
                                            ) : null}
                                        </>
                                    ) : (
                                        <p className="text-muted-foreground">
                                            No delivery address on this order.
                                        </p>
                                    )}
                                </div>
                                {fulfillment.order.deliveryDate ||
                                fulfillment.order.timeSlot ? (
                                    <div className="space-y-1">
                                        <p className="text-muted-foreground text-xs font-medium">
                                            Schedule
                                        </p>
                                        <p className="text-foreground">
                                            {[
                                                fulfillment.order.deliveryDate,
                                                fulfillment.order.timeSlot,
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </div>
                                ) : null}
                                {fulfillment.order.notes ? (
                                    <div className="space-y-1">
                                        <p className="text-muted-foreground text-xs font-medium">
                                            Order notes
                                        </p>
                                        <p className="text-foreground whitespace-pre-wrap">
                                            {fulfillment.order.notes}
                                        </p>
                                    </div>
                                ) : null}
                            </div>
                        </SectionCard>

                        <SectionCard icon={ReceiptText} title="Order summary">
                            <div className="space-y-2.5">
                                <SummaryRow
                                    label={`Subtotal · ${itemCount} item${itemCount === 1 ? '' : 's'}`}
                                >
                                    {fulfillment.order.subtotalLabel}
                                </SummaryRow>
                                {fulfillment.order.discountLabel ? (
                                    <SummaryRow
                                        label={
                                            fulfillment.order.couponCode
                                                ? `Discount (${fulfillment.order.couponCode})`
                                                : 'Discount'
                                        }
                                    >
                                        <span className="text-emerald-600 dark:text-emerald-400">
                                            −{fulfillment.order.discountLabel}
                                        </span>
                                    </SummaryRow>
                                ) : null}
                                <SummaryRow label="Delivery">
                                    {fulfillment.order.deliveryFeeLabel}
                                </SummaryRow>
                                <Separator />
                                <div className="flex items-end justify-between gap-3">
                                    <span className="font-semibold">Total</span>
                                    <span className="text-xl font-bold tracking-tight tabular-nums">
                                        {fulfillment.order.totalLabel}
                                    </span>
                                </div>
                                <Separator />
                                <SummaryRow label="Payment status">
                                    <Badge
                                        variant="outline"
                                        className={paymentStatusBadgeClass(
                                            fulfillment.order.paymentStatus,
                                        )}
                                    >
                                        {fulfillment.order.paymentStatusLabel}
                                    </Badge>
                                </SummaryRow>
                                <SummaryRow label="Payment method">
                                    {paymentMethodLabel(
                                        fulfillment.order.paymentMethod,
                                    )}
                                </SummaryRow>
                            </div>
                        </SectionCard>
                    </aside>
                </div>
            </div>
        </>
    );
}
