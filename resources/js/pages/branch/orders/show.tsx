import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
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
                                    <Badge>Paid</Badge>
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
                                        Mark as paid
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
