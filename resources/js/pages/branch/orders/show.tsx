import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import OrderAssignController from '@/actions/App/Http/Controllers/Branch/OrderAssignController';
import OrderAssignHistoryController from '@/actions/App/Http/Controllers/Branch/Reports/OrderAssignHistoryController';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
import StockTransferController from '@/actions/App/Http/Controllers/Branch/StockTransferController';
import { OrderHandoverActions } from '@/components/orders/order-handover-actions';
import { OrderStatusProgress } from '@/components/orders/order-status-progress';
import { OrderReturnRequest } from '@/components/orders/order-return-request';
import { OrderSourceBadge } from '@/components/orders/order-source-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

const REASON_MAX = 255;

type FulfillmentDetail = {
    id: number;
    fulfillmentNumber: string;
    status: string;
    actions: { key: string; label: string; status: string }[];
    shippingCostLabel: string;
    distanceKmLabel: string;
    isPaid: boolean;
    drivers: { id: number; name: string }[];
    driverId: number | null;
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
        source: string;
        sourceLabel: string;
        notes: string | null;
        deliveryDate: string | null;
        timeSlot: string | null;
        paymentMethod: string;
        paymentStatus: string;
        paymentStatusLabel: string;
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
    const [processing, setProcessing] = useState(false);
    const isReassign = fulfillment.driverId !== null;
    const needsAssignReason = isReassign || fulfillment.status === 'returned';

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

    return (
        <>
            <Head title={`Order ${fulfillment.fulfillmentNumber}`} />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-foreground text-2xl font-bold tracking-tight">
                            {fulfillment.fulfillmentNumber}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Order {fulfillment.order.orderNumber} ·{' '}
                            {fulfillment.order.customerName}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
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
                                Driver assign history
                            </Link>
                        </Button>
                        <OrderSourceBadge
                            source={fulfillment.order.source}
                            label={fulfillment.order.sourceLabel}
                        />
                        <Badge className="capitalize">
                            {status.replaceAll('_', ' ')}
                        </Badge>
                    </div>
                </div>

                <div className="grid items-start gap-4 lg:grid-cols-2">
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Items</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Product</TableHead>
                                            <TableHead>Qty</TableHead>
                                            <TableHead>Unit price</TableHead>
                                            <TableHead className="text-right">
                                                Subtotal
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {fulfillment.items.map((item) => (
                                            <TableRow key={item.name}>
                                                <TableCell>
                                                    {item.name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell>
                                                    {item.unitPriceLabel}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.subtotalLabel}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                <div className="mt-4 flex items-center justify-between border-t pt-4">
                                    <span className="text-sm font-medium">
                                        Shipping (this fulfillment)
                                    </span>
                                    <span className="text-sm font-semibold">
                                        {fulfillment.shippingCostLabel}
                                    </span>
                                </div>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    Distance: {fulfillment.distanceKmLabel}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Delivery address</CardTitle>
                            </CardHeader>
                            <CardContent className="text-muted-foreground space-y-0.5 text-sm">
                                <p>{address?.name}</p>
                                {address?.company ? (
                                    <p className="text-foreground">
                                        {address.company}
                                    </p>
                                ) : null}
                                <p>{address?.address}</p>
                                <p>
                                    {[address?.city, address?.postal_code]
                                        .filter(Boolean)
                                        .join(' ')}
                                </p>
                                <p>
                                    {address?.phone ??
                                        fulfillment.order.customerPhone}
                                </p>
                                {fulfillment.order.deliveryDate ||
                                fulfillment.order.timeSlot ? (
                                    <p className="pt-1">
                                        Deliver{' '}
                                        {[
                                            fulfillment.order.deliveryDate,
                                            fulfillment.order.timeSlot,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                ) : null}
                                {fulfillment.order.notes ? (
                                    <p className="border-border/70 mt-2 border-t pt-2 whitespace-pre-wrap">
                                        {fulfillment.order.notes}
                                    </p>
                                ) : null}
                            </CardContent>
                        </Card>

                        {fulfillment.transfers.length > 0 ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Stock transfers</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {fulfillment.transfers.map((transfer) => (
                                        <div
                                            key={transfer.id}
                                            className="space-y-2 rounded-md border p-3 text-sm"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span>
                                                    {transfer.sourceName} →{' '}
                                                    {transfer.destinationName}
                                                </span>
                                                <Badge variant="secondary">
                                                    {transfer.statusLabel}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground">
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
                                                    onClick={() => {
                                                        setProcessing(true);
                                                        router.post(
                                                            StockTransferController.receive.url(
                                                                [
                                                                    branch.slug,
                                                                    transfer.id,
                                                                ],
                                                            ),
                                                            {},
                                                            {
                                                                onFinish: () =>
                                                                    setProcessing(
                                                                        false,
                                                                    ),
                                                            },
                                                        );
                                                    }}
                                                >
                                                    Receive
                                                </Button>
                                            ) : null}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        ) : null}
                    </div>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Update status</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {fulfillment.actions.length > 0 ? (
                                    fulfillment.actions.map((action) => (
                                        <Button
                                            key={action.key}
                                            className="w-full"
                                            disabled={processing}
                                            onClick={() => {
                                                setProcessing(true);
                                                router.put(
                                                    OrderController.updateStatus.url(
                                                        [
                                                            branch.slug,
                                                            fulfillment.id,
                                                        ],
                                                    ),
                                                    { status: action.status },
                                                    {
                                                        onFinish: () =>
                                                            setProcessing(
                                                                false,
                                                            ),
                                                    },
                                                );
                                            }}
                                        >
                                            {action.label}
                                        </Button>
                                    ))
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        No status change is available from here.
                                    </p>
                                )}
                                {fulfillment.canResendCode ? (
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.resendPlacementCode.url(
                                                    [
                                                        branch.slug,
                                                        fulfillment.id,
                                                    ],
                                                ),
                                                {},
                                                {
                                                    preserveScroll: true,
                                                    onError: (errors) =>
                                                        toast.error(
                                                            errors.code ??
                                                                'The delivery code could not be sent.',
                                                        ),
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Resend delivery code
                                    </Button>
                                ) : null}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Payment</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-sm">
                                    Method:{' '}
                                    <span className="font-medium">
                                        {fulfillment.order.paymentMethod}
                                    </span>
                                </p>
                                <p className="text-sm">
                                    Order payment status:{' '}
                                    <span className="font-medium">
                                        {fulfillment.order.paymentStatusLabel}
                                    </span>
                                </p>
                                {fulfillment.isPaid ? (
                                    <Badge>Collected</Badge>
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        Payment is collected when the delivery
                                        code is confirmed.
                                    </p>
                                )}
                                <OrderReturnRequest
                                    canRequest={false}
                                    request={
                                        fulfillment.order.returnRequest ?? null
                                    }
                                    approveUrl={
                                        fulfillment.order.returnRequest
                                            ? OrderController.approveReturn.url(
                                                  [
                                                      branch.slug,
                                                      fulfillment.id,
                                                      fulfillment.order
                                                          .returnRequest.id,
                                                  ],
                                              )
                                            : undefined
                                    }
                                    rejectUrl={
                                        fulfillment.order.returnRequest
                                            ? OrderController.rejectReturn.url([
                                                  branch.slug,
                                                  fulfillment.id,
                                                  fulfillment.order
                                                      .returnRequest.id,
                                              ])
                                            : undefined
                                    }
                                />
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
                                            ? OrderController.resendPlacementCode.form(
                                                  [branch.slug, fulfillment.id],
                                              )
                                            : null
                                    }
                                    withoutCodeForm={OrderController.deliverWithoutCode.form(
                                        [branch.slug, fulfillment.id],
                                    )}
                                    canComplete={
                                        fulfillment.canComplete ?? false
                                    }
                                    canReportUnavailable={
                                        fulfillment.canReportUnavailable ??
                                        false
                                    }
                                    canCompleteWithoutCode={
                                        fulfillment.canCompleteWithoutCode ??
                                        false
                                    }
                                    failureReasons={
                                        fulfillment.failureReasons ?? []
                                    }
                                    verificationMethods={
                                        fulfillment.verificationMethods ?? []
                                    }
                                />
                            </CardContent>
                        </Card>

                        {fulfillment.canAssign ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        {isReassign
                                            ? 'Change driver'
                                            : 'Assign driver'}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
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
                                    {(
                                        [
                                            {
                                                label: 'Assign',
                                                advance: undefined,
                                            },
                                            {
                                                label: 'Assign and picked up',
                                                advance: 'picked_up',
                                            },
                                            {
                                                label: 'Assign, picked up, and on the way',
                                                advance: 'out_for_delivery',
                                            },
                                        ] as const
                                    ).map((action) => (
                                        <Button
                                            key={action.label}
                                            className="w-full"
                                            variant={
                                                action.advance
                                                    ? 'outline'
                                                    : 'default'
                                            }
                                            disabled={
                                                processing ||
                                                driverId === '' ||
                                                (needsAssignReason &&
                                                    assignReason.trim() === '')
                                            }
                                            onClick={() => {
                                                setProcessing(true);
                                                router.post(
                                                    OrderAssignController.store.url(
                                                        [
                                                            branch.slug,
                                                            fulfillment.id,
                                                        ],
                                                    ),
                                                    {
                                                        driver_id:
                                                            Number(driverId),
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
                                                        onFinish: () =>
                                                            setProcessing(
                                                                false,
                                                            ),
                                                    },
                                                );
                                            }}
                                        >
                                            {action.label}
                                        </Button>
                                    ))}
                                </CardContent>
                            </Card>
                        ) : null}

                        <Card>
                            <CardHeader>
                                <CardTitle>Order actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {fulfillment.canCancel ? (
                                    <Button
                                        className="w-full"
                                        variant="outline"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.cancel.url([
                                                    branch.slug,
                                                    fulfillment.id,
                                                ]),
                                                {},
                                                {
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Cancel order
                                    </Button>
                                ) : null}
                                {fulfillment.canRetry ? (
                                    <Button
                                        className="w-full"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.retryDelivery.url(
                                                    [
                                                        branch.slug,
                                                        fulfillment.id,
                                                    ],
                                                ),
                                                {},
                                                {
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Retry delivery
                                    </Button>
                                ) : null}
                                {fulfillment.canReschedule ? (
                                    <Button
                                        className="w-full"
                                        variant="outline"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.rescheduleDelivery.url(
                                                    [
                                                        branch.slug,
                                                        fulfillment.id,
                                                    ],
                                                ),
                                                {},
                                                {
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Reschedule
                                    </Button>
                                ) : null}
                                {fulfillment.canReturn ? (
                                    <Button
                                        className="w-full"
                                        variant="outline"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.returnDelivery.url(
                                                    [
                                                        branch.slug,
                                                        fulfillment.id,
                                                    ],
                                                ),
                                                {},
                                                {
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Start return
                                    </Button>
                                ) : null}
                                {fulfillment.canConfirmReturn ? (
                                    <Button
                                        className="w-full"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.confirmReturn.url(
                                                    [
                                                        branch.slug,
                                                        fulfillment.id,
                                                    ],
                                                ),
                                                {},
                                                {
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Confirm return
                                    </Button>
                                ) : null}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Order progress</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OrderStatusProgress steps={fulfillment.timeline} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
