import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, MapPin, Package, Truck, UserRound } from 'lucide-react';
import { useState } from 'react';
import OrderAssignController from '@/actions/App/Http/Controllers/Platform/OrderAssignController';
import OrderAssignHistoryController from '@/actions/App/Http/Controllers/Platform/Reports/OrderAssignHistoryController';
import OrderController from '@/actions/App/Http/Controllers/Platform/OrderController';
import StockTransferController from '@/actions/App/Http/Controllers/Platform/StockTransferController';
import { OrderAssignDriverSheet } from '@/components/branch/order-assign-driver-sheet';
import { OrderHandoverActions } from '@/components/orders/order-handover-actions';
import { OrderStatusTrack } from '@/components/orders/order-status-track';
import { OrderReturnRequest } from '@/components/orders/order-return-request';
import { OrderSourceBadge } from '@/components/orders/order-source-badge';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

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
    failureReasons?: { value: string; label: string }[];
    canResendCode: boolean;
    paymentMethod: string | null;
    customerName: string;
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
    const [processing, setProcessing] = useState(false);
    const [assignOpen, setAssignOpen] = useState(false);

    const run = (action: () => void) => {
        setProcessing(true);
        action();
    };

    return (
        <Card className="border-border/70 overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/30 space-y-3 border-b pb-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 space-y-1">
                        <div className="flex items-center gap-2">
                            <Package
                                className="text-muted-foreground size-4 shrink-0"
                                aria-hidden
                            />
                            <CardTitle className="text-base font-semibold tracking-tight">
                                {fulfillment.fulfillmentNumber}
                            </CardTitle>
                        </div>
                        <p className="text-muted-foreground text-sm">
                            {fulfillment.branchName}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={OrderAssignHistoryController.index.url({
                                    query: {
                                        filter: {
                                            order_id:
                                                fulfillment.fulfillmentNumber,
                                        },
                                    },
                                })}
                            >
                                Driver assign history
                            </Link>
                        </Button>
                        <Badge variant="secondary">
                            {fulfillment.statusLabel}
                        </Badge>
                        <Badge
                            variant={
                                fulfillment.isPaid ? 'default' : 'secondary'
                            }
                        >
                            {fulfillment.isPaid
                                ? 'Collected'
                                : 'Pending collection'}
                        </Badge>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-5 pt-5">
                <div>
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <h3 className="text-sm font-semibold tracking-tight">
                            Items
                        </h3>
                        <span className="text-muted-foreground text-xs font-medium tracking-wider uppercase">
                            {fulfillment.items.length} item
                            {fulfillment.items.length === 1 ? '' : 's'}
                        </span>
                    </div>
                    <ul className="border-border space-y-3 border-y py-3">
                        {fulfillment.items.map((item, index) => (
                            <li
                                key={`${item.name}-${index}`}
                                className="flex items-center gap-3"
                            >
                                <div className="bg-muted size-12 shrink-0 overflow-hidden rounded-md border">
                                    <StorefrontProductImage
                                        src={item.image}
                                        alt={item.name}
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-foreground truncate text-sm font-medium">
                                        {item.name}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        Qty {item.quantity}
                                    </p>
                                </div>
                                <p className="text-foreground shrink-0 text-sm font-semibold tabular-nums">
                                    {item.subtotalLabel}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <p className="text-muted-foreground mt-3 text-sm">
                        Shipping cost{' '}
                        <span className="text-foreground font-medium">
                            {fulfillment.shippingCostLabel}
                        </span>
                    </p>
                </div>

                {fulfillment.transfers.length > 0 ? (
                    <div className="space-y-3">
                        <h3 className="text-sm font-semibold tracking-tight">
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
                                <div className="flex flex-wrap gap-2">
                                    {transfer.canApprove ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                run(() =>
                                                    router.post(
                                                        StockTransferController.approve.url(
                                                            transfer.id,
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    ),
                                                )
                                            }
                                        >
                                            Approve
                                        </Button>
                                    ) : null}
                                    {transfer.canPick ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                run(() =>
                                                    router.post(
                                                        StockTransferController.pick.url(
                                                            transfer.id,
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    ),
                                                )
                                            }
                                        >
                                            Pick
                                        </Button>
                                    ) : null}
                                    {transfer.canDispatch ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                run(() =>
                                                    router.post(
                                                        StockTransferController.dispatch.url(
                                                            transfer.id,
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    ),
                                                )
                                            }
                                        >
                                            Dispatch
                                        </Button>
                                    ) : null}
                                    {transfer.canReceive ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() =>
                                                run(() =>
                                                    router.post(
                                                        StockTransferController.receive.url(
                                                            transfer.id,
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    ),
                                                )
                                            }
                                        >
                                            Receive
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : null}
            </CardContent>
        </Card>
    );
}

function FulfillmentActions({
    orderId,
    fulfillment,
    canResendCode,
}: {
    orderId: number;
    fulfillment: FulfillmentSummary;
    canResendCode: boolean;
}) {
    const [processing, setProcessing] = useState(false);

    const run = (action: () => void) => {
        setProcessing(true);
        action();
    };

    return (
        <Card className="border-border/70 shadow-sm">
            <CardHeader className="pb-3">
                <CardTitle className="text-base">Fulfillment actions</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {fulfillment.actions.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                        {fulfillment.actions.map((action) => (
                            <Button
                                key={action.key}
                                disabled={processing}
                                onClick={() =>
                                    run(() =>
                                        router.put(
                                            OrderController.updateStatus.url([
                                                orderId,
                                                fulfillment.id,
                                            ]),
                                            { status: action.status },
                                            {
                                                onFinish: () =>
                                                    setProcessing(false),
                                            },
                                        ),
                                    )
                                }
                            >
                                {action.label}
                            </Button>
                        ))}
                    </div>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        No status change is available from here.
                    </p>
                )}
                {canResendCode ? (
                    <Button
                        variant="outline"
                        disabled={processing}
                        onClick={() =>
                            run(() =>
                                router.post(
                                    OrderController.resendPlacementCode.url(
                                        orderId,
                                    ),
                                    {},
                                    {
                                        onFinish: () => setProcessing(false),
                                    },
                                ),
                            )
                        }
                    >
                        Resend delivery code
                    </Button>
                ) : null}

                {/* {fulfillment.canAssign ? (
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            {fulfillment.driverName ? (
                                <p className="text-muted-foreground text-sm sm:flex-1">
                                    Current driver:{' '}
                                    <span className="text-foreground font-medium">
                                        {fulfillment.driverName}
                                    </span>
                                </p>
                            ) : (
                                <p className="text-muted-foreground text-sm sm:flex-1">
                                    No driver assigned
                                </p>
                            )}
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
                                fulfillmentNumber={
                                    fulfillment.fulfillmentNumber
                                }
                                mode={
                                    fulfillment.driverId ? 'change' : 'assign'
                                }
                                candidatesUrl={OrderAssignController.candidates.url(
                                    orderId,
                                )}
                                storeUrl={OrderAssignController.store.url(
                                    orderId,
                                )}
                            />
                        </div>
                    ) : null} */}

                <div className="flex flex-wrap gap-2">
                    {fulfillment.canCancel ? (
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() =>
                                run(() =>
                                    router.post(
                                        OrderController.cancel.url(orderId),
                                        {},
                                        {
                                            onFinish: () =>
                                                setProcessing(false),
                                        },
                                    ),
                                )
                            }
                        >
                            Cancel order
                        </Button>
                    ) : null}
                    {fulfillment.canRetry ? (
                        <Button
                            disabled={processing}
                            onClick={() =>
                                run(() =>
                                    router.post(
                                        OrderController.retryDelivery.url(
                                            orderId,
                                        ),
                                        {},
                                        {
                                            onFinish: () =>
                                                setProcessing(false),
                                        },
                                    ),
                                )
                            }
                        >
                            Retry delivery
                        </Button>
                    ) : null}
                    {fulfillment.canReschedule ? (
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() =>
                                run(() =>
                                    router.post(
                                        OrderController.rescheduleDelivery.url(
                                            orderId,
                                        ),
                                        {},
                                        {
                                            onFinish: () =>
                                                setProcessing(false),
                                        },
                                    ),
                                )
                            }
                        >
                            Reschedule
                        </Button>
                    ) : null}
                    {fulfillment.canReturn ? (
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() =>
                                run(() =>
                                    router.post(
                                        OrderController.returnDelivery.url(
                                            orderId,
                                        ),
                                        {},
                                        {
                                            onFinish: () =>
                                                setProcessing(false),
                                        },
                                    ),
                                )
                            }
                        >
                            Begin return
                        </Button>
                    ) : null}
                    {fulfillment.canConfirmReturn ? (
                        <Button
                            disabled={processing}
                            onClick={() =>
                                run(() =>
                                    router.post(
                                        OrderController.confirmReturn.url(
                                            orderId,
                                        ),
                                        {},
                                        {
                                            onFinish: () =>
                                                setProcessing(false),
                                        },
                                    ),
                                )
                            }
                        >
                            Confirm return
                        </Button>
                    ) : null}
                </div>
            </CardContent>
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

    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="space-y-5 p-2 md:p-4">
                <div className="border-border/70 from-muted/40 via-background to-background relative overflow-hidden rounded-xl border bg-linear-to-br p-5 shadow-sm sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0 space-y-2">
                            <p className="text-muted-foreground text-xs font-semibold tracking-[0.14em] uppercase">
                                Platform order
                            </p>
                            <h1 className="text-foreground text-2xl font-bold tracking-tight sm:text-3xl">
                                {order.orderNumber}
                            </h1>
                            <div className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <span className="text-foreground inline-flex items-center gap-1.5 font-medium">
                                    <UserRound
                                        className="size-3.5"
                                        aria-hidden
                                    />
                                    {order.customerName}
                                </span>
                                {order.placedAt ? (
                                    <span>Placed {order.placedAt}</span>
                                ) : null}
                                {order.placedByName ? (
                                    <span>by {order.placedByName}</span>
                                ) : null}
                                <span className="text-foreground font-semibold tabular-nums">
                                    {order.totalLabel}
                                </span>
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
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge className="px-2.5 py-1 text-sm">
                                {order.statusLabel}
                            </Badge>
                            <OrderSourceBadge
                                source={order.source}
                                label={order.sourceLabel}
                                className="px-2.5 py-1 text-sm"
                            />
                            <Badge
                                variant={
                                    order.paymentStatus === 'collected' ||
                                    order.paymentStatus === 'verified'
                                        ? 'default'
                                        : 'secondary'
                                }
                                className="px-2.5 py-1 text-sm"
                            >
                                {order.paymentStatusLabel}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div className="grid items-start gap-5 lg:grid-cols-2">
                    <div className="space-y-5">
                        {order.fulfillments.map((fulfillment) => (
                            <FulfillmentCard
                                canResendCode={order.canResendCode}
                                key={fulfillment.id}
                                orderId={order.id}
                                fulfillment={fulfillment}
                            />
                        ))}
                        <OrderHandoverActions
                            orderId={order.id}
                            deliverForm={OrderController.deliver.form(order.id)}
                            failForm={OrderController.fail.form(order.id)}
                            canComplete={order.canComplete ?? false}
                            canReportUnavailable={
                                order.canReportUnavailable ?? false
                            }
                            failureReasons={order.failureReasons ?? []}
                        />
                    </div>

                    <aside className="space-y-4">
                        <Card className="border-border/70 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <MapPin
                                        className="text-muted-foreground size-4"
                                        aria-hidden
                                    />
                                    Delivery address
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1 text-sm">
                                <p className="text-foreground font-semibold">
                                    {address?.name}
                                </p>
                                {address?.company ? (
                                    <p className="text-foreground">
                                        {address.company}
                                    </p>
                                ) : null}
                                <p className="text-muted-foreground">
                                    {address?.address}
                                </p>
                                {cityLine ? (
                                    <p className="text-muted-foreground">
                                        {cityLine}
                                    </p>
                                ) : null}
                                <p className="text-muted-foreground">
                                    {address?.phone}
                                </p>
                                {address?.email ? (
                                    <p className="text-muted-foreground">
                                        {address.email}
                                    </p>
                                ) : null}
                                {order.notes ? (
                                    <p className="text-muted-foreground border-border/70 mt-2 border-t pt-2 whitespace-pre-wrap">
                                        {order.notes}
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">
                                    Totals
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2.5 text-sm">
                                <div className="flex justify-between gap-3">
                                    <span className="text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        {order.subtotalLabel}
                                    </span>
                                </div>
                                {order.discountLabel ? (
                                    <div className="flex justify-between gap-3">
                                        <span className="text-muted-foreground">
                                            {order.couponCode ?? 'Discount'}
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {order.discountLabel}
                                        </span>
                                    </div>
                                ) : null}
                                <div className="flex justify-between gap-3">
                                    <span className="text-muted-foreground">
                                        Delivery
                                    </span>
                                    <span className="font-medium tabular-nums">
                                        {order.deliveryFeeLabel}
                                    </span>
                                </div>
                                <Separator />
                                <div className="flex items-end justify-between gap-3">
                                    <span className="font-semibold">Total</span>
                                    <span className="text-xl font-bold tracking-tight tabular-nums">
                                        {order.totalLabel}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-border/70 shadow-sm">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">
                                    Payment
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="bg-muted/40 space-y-1 rounded-lg p-3 text-sm">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="text-muted-foreground">
                                            Status
                                        </span>
                                        <span className="font-medium">
                                            {order.paymentStatusLabel}
                                        </span>
                                    </div>
                                    {order.paymentMethod ? (
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="text-muted-foreground">
                                                Method
                                            </span>
                                            <span className="font-medium capitalize">
                                                {order.paymentMethod.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </span>
                                        </div>
                                    ) : null}
                                </div>
                                <OrderReturnRequest
                                    canRequest={false}
                                    request={order.returnRequest ?? null}
                                    approveUrl={
                                        order.returnRequest
                                            ? OrderController.approveReturn.url(
                                                  {
                                                      order: order.id,
                                                      orderReturn:
                                                          order.returnRequest
                                                              .id,
                                                  },
                                              )
                                            : undefined
                                    }
                                    rejectUrl={
                                        order.returnRequest
                                            ? OrderController.rejectReturn.url({
                                                  order: order.id,
                                                  orderReturn:
                                                      order.returnRequest.id,
                                              })
                                            : undefined
                                    }
                                />
                            </CardContent>
                        </Card>

                        {order.fulfillments.map((fulfillment) => (
                            <FulfillmentActions
                                key={fulfillment.id}
                                orderId={order.id}
                                fulfillment={fulfillment}
                                canResendCode={order.canResendCode}
                            />
                        ))}
                    </aside>

                    <Card className="border-border/70 shadow-sm lg:col-span-2">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Truck
                                    className="text-muted-foreground size-4"
                                    aria-hidden
                                />
                                History
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <OrderStatusTrack steps={order.timeline} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
