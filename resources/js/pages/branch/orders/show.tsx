import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
import StockTransferController from '@/actions/App/Http/Controllers/Branch/StockTransferController';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type FulfillmentDetail = {
    id: number;
    fulfillmentNumber: string;
    status: string;
    statusOptions: { value: string; label: string }[];
    shippingCostLabel: string;
    distanceKmLabel: string;
    isPaid: boolean;
    drivers: { id: number; name: string }[];
    driverId: number | null;
    canAssign: boolean;
    canCancel: boolean;
    canRetry: boolean;
    canReschedule: boolean;
    canReturn: boolean;
    canConfirmReturn: boolean;
    order: {
        id: number;
        orderNumber: string;
        customerName: string;
        paymentMethod: string;
        paymentStatus: string;
        shippingAddress: {
            name?: string;
            address?: string;
            city?: string;
            postal_code?: string;
            phone?: string;
        } | null;
    };
    items: {
        name: string;
        image: string;
        quantity: number;
        unitPriceLabel: string;
        subtotalLabel: string;
    }[];
    timeline: { label: string; detail: string; comment: string | null }[];
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
    const [status, setStatus] = useState(fulfillment.status);
    const [driverId, setDriverId] = useState(
        fulfillment.driverId ? String(fulfillment.driverId) : '',
    );
    const [processing, setProcessing] = useState(false);

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
                    <Badge className="capitalize">
                        {status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
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
                                            <TableRow
                                                key={item.name}
                                            >
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
                                <CardTitle>History</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {fulfillment.timeline.map((step, index) => (
                                    <div
                                        key={`${step.label}-${index}`}
                                        className="text-sm"
                                    >
                                        <p className="font-medium">
                                            {step.label}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {step.detail}
                                        </p>
                                    </div>
                                ))}
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
                                <Select
                                    value={status}
                                    onValueChange={setStatus}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {fulfillment.statusOptions.map(
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
                                        status === fulfillment.status
                                    }
                                    onClick={() => {
                                        setProcessing(true);
                                        router.put(
                                            OrderController.updateStatus.url([
                                                branch.slug,
                                                fulfillment.id,
                                            ]),
                                            { status },
                                            {
                                                onFinish: () =>
                                                    setProcessing(false),
                                            },
                                        );
                                    }}
                                >
                                    Save status
                                </Button>
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
                                    <span className="font-medium capitalize">
                                        {fulfillment.order.paymentStatus}
                                    </span>
                                </p>
                                {fulfillment.isPaid ? (
                                    <Badge>Collected</Badge>
                                ) : (
                                    <Button
                                        className="w-full"
                                        disabled={processing}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.markPaid.url([
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
                                        Mark collected
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {fulfillment.canAssign ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Assign driver</CardTitle>
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
                                    <Button
                                        className="w-full"
                                        disabled={processing || driverId === ''}
                                        onClick={() => {
                                            setProcessing(true);
                                            router.post(
                                                OrderController.assignDriver.url(
                                                    [
                                                        branch.slug,
                                                        fulfillment.id,
                                                    ],
                                                ),
                                                { driver_id: Number(driverId) },
                                                {
                                                    onFinish: () =>
                                                        setProcessing(false),
                                                },
                                            );
                                        }}
                                    >
                                        Assign driver
                                    </Button>
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
            </div>
        </>
    );
}
