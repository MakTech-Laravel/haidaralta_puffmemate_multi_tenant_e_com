import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Platform/OrderController';
import StockTransferController from '@/actions/App/Http/Controllers/Platform/StockTransferController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type FulfillmentSummary = {
    id: number;
    fulfillmentNumber: string;
    branchName: string;
    status: string;
    statusOptions: { value: string; label: string }[];
    shippingCostLabel: string;
    isPaid: boolean;
    driverId: number | null;
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
    paymentStatus: string;
    paymentStatusOptions: { value: string; label: string }[];
    customerName: string;
    totalLabel: string;
    subtotalLabel: string;
    deliveryFeeLabel: string;
    shippingAddress: {
        name?: string;
        address?: string;
        city?: string;
        postal_code?: string;
        phone?: string;
    } | null;
    drivers: { id: number; name: string }[];
    fulfillments: FulfillmentSummary[];
    timeline: { label: string; detail: string; comment: string | null }[];
};

function FulfillmentCard({
    orderId,
    fulfillment,
    drivers,
}: {
    orderId: number;
    fulfillment: FulfillmentSummary;
    drivers: { id: number; name: string }[];
}) {
    const [status, setStatus] = useState(fulfillment.status);
    const [driverId, setDriverId] = useState(
        fulfillment.driverId ? String(fulfillment.driverId) : '',
    );
    const [processing, setProcessing] = useState(false);

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-3">
                <div>
                    <CardTitle className="text-base">
                        {fulfillment.fulfillmentNumber}
                    </CardTitle>
                    <p className="text-muted-foreground text-sm">
                        {fulfillment.branchName}
                    </p>
                </div>
                <Badge variant={fulfillment.isPaid ? 'default' : 'secondary'}>
                    {fulfillment.isPaid ? 'Collected' : 'Pending collection'}
                </Badge>
            </CardHeader>
            <CardContent className="space-y-3">
                <ul className="space-y-1 text-sm">
                    {fulfillment.items.map((item) => (
                        <li
                            key={item.name}
                            className="flex items-center justify-between gap-3"
                        >
                            <span>
                                {item.name} × {item.quantity}
                            </span>
                            <span className="font-medium">
                                {item.subtotalLabel}
                            </span>
                        </li>
                    ))}
                </ul>
                <p className="text-muted-foreground text-sm">
                    Shipping: {fulfillment.shippingCostLabel}
                </p>

                {fulfillment.transfers.length > 0 ? (
                    <div className="space-y-2">
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
                                <div className="flex flex-wrap gap-2">
                                    {transfer.canApprove ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() => {
                                                setProcessing(true);
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
                                                );
                                            }}
                                        >
                                            Approve
                                        </Button>
                                    ) : null}
                                    {transfer.canPick ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() => {
                                                setProcessing(true);
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
                                                );
                                            }}
                                        >
                                            Pick
                                        </Button>
                                    ) : null}
                                    {transfer.canDispatch ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() => {
                                                setProcessing(true);
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
                                                );
                                            }}
                                        >
                                            Dispatch
                                        </Button>
                                    ) : null}
                                    {transfer.canReceive ? (
                                        <Button
                                            size="sm"
                                            disabled={processing}
                                            onClick={() => {
                                                setProcessing(true);
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
                                                );
                                            }}
                                        >
                                            Receive
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : null}

                <div className="flex items-center gap-2">
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="flex-1">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {fulfillment.statusOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        size="sm"
                        disabled={processing || status === fulfillment.status}
                        onClick={() => {
                            setProcessing(true);
                            router.put(
                                OrderController.updateStatus.url([
                                    orderId,
                                    fulfillment.id,
                                ]),
                                { status },
                                { onFinish: () => setProcessing(false) },
                            );
                        }}
                    >
                        Save
                    </Button>
                </div>

                {fulfillment.canAssign ? (
                    <div className="flex items-center gap-2">
                        <Select value={driverId} onValueChange={setDriverId}>
                            <SelectTrigger className="flex-1">
                                <SelectValue placeholder="Choose a driver" />
                            </SelectTrigger>
                            <SelectContent>
                                {drivers.map((driver) => (
                                    <SelectItem
                                        key={driver.id}
                                        value={String(driver.id)}
                                    >
                                        {driver.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            size="sm"
                            disabled={processing || driverId === ''}
                            onClick={() => {
                                setProcessing(true);
                                router.post(
                                    OrderController.assignDriver.url(orderId),
                                    { driver_id: Number(driverId) },
                                    { onFinish: () => setProcessing(false) },
                                );
                            }}
                        >
                            Assign
                        </Button>
                    </div>
                ) : null}

                <div className="flex flex-wrap gap-2">
                    {fulfillment.canCancel ? (
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setProcessing(true);
                                router.post(
                                    OrderController.cancel.url(orderId),
                                    {},
                                    { onFinish: () => setProcessing(false) },
                                );
                            }}
                        >
                            Cancel
                        </Button>
                    ) : null}
                    {fulfillment.canRetry ? (
                        <Button
                            size="sm"
                            disabled={processing}
                            onClick={() => {
                                setProcessing(true);
                                router.post(
                                    OrderController.retryDelivery.url(orderId),
                                    {},
                                    { onFinish: () => setProcessing(false) },
                                );
                            }}
                        >
                            Retry
                        </Button>
                    ) : null}
                    {fulfillment.canReschedule ? (
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setProcessing(true);
                                router.post(
                                    OrderController.rescheduleDelivery.url(
                                        orderId,
                                    ),
                                    {},
                                    { onFinish: () => setProcessing(false) },
                                );
                            }}
                        >
                            Reschedule
                        </Button>
                    ) : null}
                    {fulfillment.canReturn ? (
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setProcessing(true);
                                router.post(
                                    OrderController.returnDelivery.url(orderId),
                                    {},
                                    { onFinish: () => setProcessing(false) },
                                );
                            }}
                        >
                            Return
                        </Button>
                    ) : null}
                    {fulfillment.canConfirmReturn ? (
                        <Button
                            size="sm"
                            disabled={processing}
                            onClick={() => {
                                setProcessing(true);
                                router.post(
                                    OrderController.confirmReturn.url(orderId),
                                    {},
                                    { onFinish: () => setProcessing(false) },
                                );
                            }}
                        >
                            Confirm return
                        </Button>
                    ) : null}
                </div>
            </CardContent>
        </Card>
    );
}

export default function PlatformOrderShow({
    order,
}: {
    order: OrderDetail;
}) {
    const [paymentStatus, setPaymentStatus] = useState(order.paymentStatus);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Orders', href: '/platform/orders' },
            { title: order.orderNumber, href: '#' },
        ],
    });

    const address = order.shippingAddress;

    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-foreground text-2xl font-bold tracking-tight">
                            {order.orderNumber}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {order.customerName} · {order.totalLabel}
                        </p>
                    </div>
                    <Badge className="capitalize">
                        {order.status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="space-y-4">
                        {order.fulfillments.map((fulfillment) => (
                            <FulfillmentCard
                                key={fulfillment.id}
                                orderId={order.id}
                                fulfillment={fulfillment}
                                drivers={order.drivers}
                            />
                        ))}
                    </div>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Delivery address</CardTitle>
                            </CardHeader>
                            <CardContent className="text-muted-foreground space-y-0.5 text-sm">
                                <p>{address?.name}</p>
                                <p>{address?.address}</p>
                                <p>
                                    {[address?.city, address?.postal_code]
                                        .filter(Boolean)
                                        .join(' ')}
                                </p>
                                <p>{address?.phone}</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Totals</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1 text-sm">
                                <div className="flex justify-between">
                                    <span>Subtotal</span>
                                    <span>{order.subtotalLabel}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Delivery</span>
                                    <span>{order.deliveryFeeLabel}</span>
                                </div>
                                <div className="flex justify-between font-semibold">
                                    <span>Total</span>
                                    <span>{order.totalLabel}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Payment override</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Select
                                    value={paymentStatus}
                                    onValueChange={setPaymentStatus}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {order.paymentStatusOptions.map(
                                            (option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <Button
                                    className="w-full"
                                    disabled={
                                        processing ||
                                        paymentStatus === order.paymentStatus
                                    }
                                    onClick={() => {
                                        setProcessing(true);
                                        router.put(
                                            OrderController.markPaid.url(
                                                order.id,
                                            ),
                                            { payment_status: paymentStatus },
                                            {
                                                onFinish: () =>
                                                    setProcessing(false),
                                            },
                                        );
                                    }}
                                >
                                    Update payment status
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>History</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {order.timeline.map((step, index) => (
                                    <div
                                        key={`${step.label}-${index}`}
                                        className="text-sm"
                                    >
                                        <p className="font-medium capitalize">
                                            {step.label.replaceAll('_', ' ')}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {step.detail}
                                        </p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
