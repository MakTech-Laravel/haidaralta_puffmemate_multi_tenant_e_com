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
    Store,
    Truck,
    UserRound,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import CustomerController from '@/actions/App/Http/Controllers/Platform/CustomerController';
import OrderAssignController from '@/actions/App/Http/Controllers/Platform/OrderAssignController';
import OrderAssignHistoryController from '@/actions/App/Http/Controllers/Platform/Reports/OrderAssignHistoryController';
import OrderController from '@/actions/App/Http/Controllers/Platform/OrderController';
import StockTransferController from '@/actions/App/Http/Controllers/Platform/StockTransferController';
import { OrderAssignDriverSheet } from '@/components/branch/order-assign-driver-sheet';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { formatAppDateTime } from '@/lib/datetime';
import {
    orderStatusBadgeClass,
    paymentStatusBadgeClass,
} from '@/lib/order-badges';
import { paymentMethodLabel } from '@/lib/payment-method-label';

type FulfillmentSummary = {
    id: number;
    fulfillmentNumber: string;
    branchName: string;
    status: string;
    statusLabel: string;
    actions: { key: string; label: string; status: string }[];
    shippingCostLabel: string;
    isPaid: boolean;
    driverId: number | null;
    driverName: string | null;
    canAssign: boolean;
    canCancel: boolean;
    canRetry: boolean;
    canReschedule: boolean;
    canReturn: boolean;
    canConfirmReturn: boolean;
    items: {
        name: string;
        image: string;
        quantity: number;
        subtotalLabel: string;
    }[];
    transfers: {
        id: number;
        status: string;
        statusLabel: string;
        sourceName: string | null;
        destinationName: string | null;
        estimatedCostLabel: string;
        items: { name: string; quantity: number }[];
        canApprove: boolean;
        canPick: boolean;
        canDispatch: boolean;
        canReceive: boolean;
    }[];
};

type OrderDetail = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    paymentStatus: string;
    paymentStatusLabel: string;
    source: string;
    sourceLabel: string;
    wholesaleInquiry: { id: number; url: string | null } | null;
    notes: string | null;
    placedByName: string | null;
    paymentStatusOptions: { value: string; label: string }[];
    canComplete?: boolean;
    returnRequest?: {
        id: number;
        type: string;
        typeLabel: string;
        reason: string;
        note: string | null;
        status: string;
        statusLabel: string;
    } | null;
    canReportUnavailable?: boolean;
    canCompleteWithoutCode?: boolean;
    failureReasons?: { value: string; label: string }[];
    verificationMethods?: { value: string; label: string }[];
    canResendCode: boolean;
    paymentMethod: string | null;
    customerId: number | null;
    customerName: string;
    customerPhone: string | null;
    placedAt: string | null;
    totalLabel: string;
    subtotalLabel: string;
    discountLabel: string | null;
    couponCode: string | null;
    deliveryFeeLabel: string;
    shippingAddress: {
        name?: string;
        address?: string;
        city?: string;
        postal_code?: string;
        phone?: string;
        company?: string | null;
        email?: string | null;
    } | null;
    fulfillments: FulfillmentSummary[];
    timeline: {
        key: string;
        label: string;
        state: 'completed' | 'current' | 'upcoming';
        actorName: string | null;
        detail: string | null;
        comment: string | null;
    }[];
};

function FulfillmentCard({
    orderId,
    fulfillment,
    canResendCode,
}: {
    orderId: number;
    fulfillment: FulfillmentSummary;
    canResendCode: boolean;
}) {
    const [pendingAction, setPendingAction] = useState<string | null>(null);
    const [assignOpen, setAssignOpen] = useState(false);
    const processing = pendingAction !== null;
    const itemCount = fulfillment.items.length;

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
            OrderController.updateStatus.url([orderId, fulfillment.id]),
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
            OrderController.resendPlacementCode.url(orderId),
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
        canResendCode;
    const hasActions = fulfillment.actions.length > 0 || hasOrderActions;

    return (
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
                    <p className="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <Store className="size-3.5 shrink-0" aria-hidden />
                        <span className="truncate">
                            {fulfillment.branchName}
                        </span>
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        className={orderStatusBadgeClass(fulfillment.status)}
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
                        {fulfillment.isPaid ? 'Collected' : 'Pending collection'}
                    </Badge>
                </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3">
                <p className="text-muted-foreground flex min-w-0 items-center gap-2 text-sm">
                    <Truck className="size-4 shrink-0" aria-hidden />
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
                <Button variant="ghost" size="sm" asChild>
                    <Link
                        href={OrderAssignHistoryController.index.url({
                            query: {
                                filter: {
                                    order_id: fulfillment.fulfillmentNumber,
                                },
                            },
                        })}
                    >
                        <History aria-hidden />
                        Driver assign history
                    </Link>
                </Button>
            </div>

            <div className="px-5 py-4">
                <div className="mb-2 flex items-center justify-between gap-3">
                    <h3 className="text-sm font-semibold">Items</h3>
                    <span className="text-muted-foreground text-xs font-medium tracking-wider uppercase">
                        {itemCount} item{itemCount === 1 ? '' : 's'}
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
                                    Qty {item.quantity}
                                </p>
                            </div>
                            <p className="text-foreground shrink-0 text-sm font-semibold tabular-nums">
                                {item.subtotalLabel}
                            </p>
                        </li>
                    ))}
                </ul>
                <div className="border-border mt-1 flex items-center justify-between gap-3 border-t pt-3 text-sm">
                    <span className="text-muted-foreground">Shipping cost</span>
                    <span className="font-medium tabular-nums">
                        {fulfillment.shippingCostLabel}
                    </span>
                </div>
            </div>

            {fulfillment.transfers.length > 0 ? (
                <div className="space-y-3 border-t px-5 py-4">
                    <h3 className="text-sm font-semibold">Stock transfers</h3>
                    {fulfillment.transfers.map((transfer) => (
                        <div
                            key={transfer.id}
                            className="bg-muted/20 space-y-3 rounded-lg border p-3"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="text-foreground flex min-w-0 items-center gap-1.5 text-sm font-medium">
                                    <span className="truncate">
                                        {transfer.sourceName ?? 'Source'}
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
                                Est. cost {transfer.estimatedCostLabel}
                            </p>
                            {transfer.canApprove ||
                            transfer.canPick ||
                            transfer.canDispatch ||
                            transfer.canReceive ? (
                                <div className="flex flex-wrap gap-2">
                                    {transfer.canApprove ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                post(
                                                    `approve-${transfer.id}`,
                                                    StockTransferController.approve.url(
                                                        transfer.id,
                                                    ),
                                                )
                                            }
                                        >
                                            {spinnerFor(
                                                `approve-${transfer.id}`,
                                            )}
                                            Approve
                                        </Button>
                                    ) : null}
                                    {transfer.canPick ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                post(
                                                    `pick-${transfer.id}`,
                                                    StockTransferController.pick.url(
                                                        transfer.id,
                                                    ),
                                                )
                                            }
                                        >
                                            {spinnerFor(`pick-${transfer.id}`)}
                                            Pick
                                        </Button>
                                    ) : null}
                                    {transfer.canDispatch ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                post(
                                                    `dispatch-${transfer.id}`,
                                                    StockTransferController.dispatch.url(
                                                        transfer.id,
                                                    ),
                                                )
                                            }
                                        >
                                            {spinnerFor(
                                                `dispatch-${transfer.id}`,
                                            )}
                                            Dispatch
                                        </Button>
                                    ) : null}
                                    {transfer.canReceive ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                post(
                                                    `receive-${transfer.id}`,
                                                    StockTransferController.receive.url(
                                                        transfer.id,
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
                            ) : null}
                        </div>
                    ))}
                </div>
            ) : null}

            <div className="bg-muted/30 space-y-3 border-t px-5 py-4">
                <h3 className="text-sm font-semibold">Fulfillment actions</h3>
                {hasActions ? (
                    <div className="flex flex-wrap gap-2">
                        {fulfillment.actions.map((action) => (
                            <Button
                                key={action.key}
                                disabled={processing}
                                onClick={() =>
                                    updateStatus(action.key, action.status)
                                }
                            >
                                {spinnerFor(action.key)}
                                {action.label}
                            </Button>
                        ))}
                        {canResendCode ? (
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
                                            orderId,
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
                                            orderId,
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
                                            orderId,
                                        ),
                                    )
                                }
                            >
                                {spinnerFor('return')}
                                Begin return
                            </Button>
                        ) : null}
                        {fulfillment.canConfirmReturn ? (
                            <Button
                                disabled={processing}
                                onClick={() =>
                                    post(
                                        'confirm-return',
                                        OrderController.confirmReturn.url(
                                            orderId,
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
                                        OrderController.cancel.url(orderId),
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

                {/* {fulfillment.canAssign ? (
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <Button
                            disabled={processing}
                            onClick={() => setAssignOpen(true)}
                        >
                            {fulfillment.driverId
                                ? 'Change driver'
                                : 'Assign driver'}
                        </Button>
                        <OrderAssignDriverSheet
                            open={assignOpen}
                            onOpenChange={setAssignOpen}
                            fulfillmentId={fulfillment.id}
                            fulfillmentNumber={fulfillment.fulfillmentNumber}
                            mode={fulfillment.driverId ? 'change' : 'assign'}
                            candidatesUrl={OrderAssignController.candidates.url(
                                orderId,
                            )}
                            storeUrl={OrderAssignController.store.url(orderId)}
                        />
                    </div>
                ) : null} */}
            </div>
        </Card>
    );
}

export default function PlatformOrderShow({ order }: { order: OrderDetail }) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Orders', href: '/platform/orders' },
            { title: order.orderNumber, href: '#' },
        ],
    });

    const address = order.shippingAddress;
    const cityLine = [address?.city, address?.postal_code]
        .filter(Boolean)
        .join(' ');
    const hasStreetAddress = Boolean(address?.address || cityLine);
    const recipientName = address?.name || order.customerName;
    const recipientPhone = address?.phone || order.customerPhone;
    const branchNames = [
        ...new Set(order.fulfillments.map((row) => row.branchName)),
    ].join(', ');
    const itemCount = order.fulfillments.reduce(
        (total, row) =>
            total + row.items.reduce((sum, item) => sum + item.quantity, 0),
        0,
    );

    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="mx-auto w-full max-w-7xl space-y-6 p-2 md:p-4">
                <header className="border-border/70 bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="from-primary/10 via-background to-background flex flex-wrap items-start justify-between gap-4 bg-linear-to-br px-5 py-5 sm:px-6">
                        <div className="min-w-0 space-y-2">
                            <p className="text-muted-foreground text-xs font-semibold tracking-[0.14em] uppercase">
                                Platform order
                            </p>
                            <h1 className="text-foreground truncate text-2xl font-bold tracking-tight sm:text-3xl">
                                {order.orderNumber}
                            </h1>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    className={orderStatusBadgeClass(
                                        order.status,
                                    )}
                                >
                                    {order.statusLabel}
                                </Badge>
                                <Badge
                                    variant="outline"
                                    className={paymentStatusBadgeClass(
                                        order.paymentStatus,
                                    )}
                                >
                                    {order.paymentStatusLabel}
                                </Badge>
                                <OrderSourceBadge
                                    source={order.source}
                                    label={order.sourceLabel}
                                />
                            </div>
                            {order.wholesaleInquiry ? (
                                <p className="text-muted-foreground text-sm">
                                    From wholesale inquiry{' '}
                                    {order.wholesaleInquiry.url ? (
                                        <Link
                                            href={order.wholesaleInquiry.url}
                                            className="text-primary font-medium hover:underline"
                                        >
                                            #{order.wholesaleInquiry.id}
                                        </Link>
                                    ) : (
                                        <span className="text-foreground font-medium">
                                            #{order.wholesaleInquiry.id}
                                        </span>
                                    )}
                                </p>
                            ) : null}
                        </div>
                        <div className="text-left sm:text-right">
                            <p className="text-muted-foreground text-xs font-medium">
                                Order total
                            </p>
                            <p className="text-foreground text-3xl font-bold tracking-tight tabular-nums">
                                {order.totalLabel}
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-4 border-t px-5 py-4 sm:grid-cols-2 sm:px-6 xl:grid-cols-4">
                        <HeaderFact icon={UserRound} label="Customer">
                            {order.customerId ? (
                                <Link
                                    href={CustomerController.show.url(
                                        order.customerId,
                                    )}
                                    className="hover:text-primary"
                                >
                                    {order.customerName}
                                </Link>
                            ) : (
                                order.customerName
                            )}
                        </HeaderFact>
                        <HeaderFact icon={CalendarClock} label="Placed">
                            {formatAppDateTime(order.placedAt) || '—'}
                            {order.placedByName ? (
                                <span className="text-muted-foreground font-normal">
                                    {' '}
                                    · by {order.placedByName}
                                </span>
                            ) : null}
                        </HeaderFact>
                        <HeaderFact icon={Store} label="Branch">
                            {branchNames || '—'}
                        </HeaderFact>
                        <HeaderFact icon={CreditCard} label="Payment method">
                            {paymentMethodLabel(order.paymentMethod)}
                        </HeaderFact>
                    </div>
                </header>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <div className="min-w-0 space-y-6">
                        {order.fulfillments.map((fulfillment) => (
                            <FulfillmentCard
                                key={fulfillment.id}
                                orderId={order.id}
                                fulfillment={fulfillment}
                                canResendCode={order.canResendCode}
                            />
                        ))}

                        <OrderHandoverActions
                            orderId={order.id}
                            deliverForm={OrderController.deliver.form(order.id)}
                            failForm={OrderController.fail.form(order.id)}
                            resendForm={
                                order.canResendCode
                                    ? OrderController.resendPlacementCode.form(
                                          order.id,
                                      )
                                    : null
                            }
                            withoutCodeForm={OrderController.deliverWithoutCode.form(
                                order.id,
                            )}
                            canComplete={order.canComplete ?? false}
                            canReportUnavailable={
                                order.canReportUnavailable ?? false
                            }
                            canCompleteWithoutCode={
                                order.canCompleteWithoutCode ?? false
                            }
                            failureReasons={order.failureReasons ?? []}
                            verificationMethods={
                                order.verificationMethods ?? []
                            }
                        />

                        <OrderReturnRequest
                            canRequest={false}
                            request={order.returnRequest ?? null}
                            approveUrl={
                                order.returnRequest
                                    ? OrderController.approveReturn.url({
                                          order: order.id,
                                          orderReturn: order.returnRequest.id,
                                      })
                                    : undefined
                            }
                            rejectUrl={
                                order.returnRequest
                                    ? OrderController.rejectReturn.url({
                                          order: order.id,
                                          orderReturn: order.returnRequest.id,
                                      })
                                    : undefined
                            }
                        />

                        <SectionCard icon={History} title="Order progress">
                            <OrderStatusProgress steps={order.timeline} />
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
                                    {address?.email ? (
                                        <p className="text-muted-foreground flex items-center gap-1.5">
                                            <Mail
                                                className="size-3.5 shrink-0"
                                                aria-hidden
                                            />
                                            <a
                                                href={`mailto:${address.email}`}
                                                className="hover:text-foreground truncate"
                                            >
                                                {address.email}
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
                                {order.notes ? (
                                    <div className="space-y-1">
                                        <p className="text-muted-foreground text-xs font-medium">
                                            Order notes
                                        </p>
                                        <p className="text-foreground whitespace-pre-wrap">
                                            {order.notes}
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
                                    {order.subtotalLabel}
                                </SummaryRow>
                                {order.discountLabel ? (
                                    <SummaryRow
                                        label={
                                            order.couponCode
                                                ? `Discount (${order.couponCode})`
                                                : 'Discount'
                                        }
                                    >
                                        <span className="text-emerald-600 dark:text-emerald-400">
                                            −{order.discountLabel}
                                        </span>
                                    </SummaryRow>
                                ) : null}
                                <SummaryRow label="Delivery">
                                    {order.deliveryFeeLabel}
                                </SummaryRow>
                                <Separator />
                                <div className="flex items-end justify-between gap-3">
                                    <span className="font-semibold">Total</span>
                                    <span className="text-xl font-bold tracking-tight tabular-nums">
                                        {order.totalLabel}
                                    </span>
                                </div>
                                <Separator />
                                <SummaryRow label="Payment status">
                                    <Badge
                                        variant="outline"
                                        className={paymentStatusBadgeClass(
                                            order.paymentStatus,
                                        )}
                                    >
                                        {order.paymentStatusLabel}
                                    </Badge>
                                </SummaryRow>
                                <SummaryRow label="Payment method">
                                    {paymentMethodLabel(order.paymentMethod)}
                                </SummaryRow>
                            </div>
                        </SectionCard>
                    </aside>
                </div>
            </div>
        </>
    );
}
