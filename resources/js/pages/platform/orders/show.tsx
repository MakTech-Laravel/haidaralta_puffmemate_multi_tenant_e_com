import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Platform/OrderController';
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
    items: {
        name: string;
        image: string;
        quantity: number;
        subtotalLabel: string;
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
    fulfillments: FulfillmentSummary[];
    timeline: { label: string; detail: string; comment: string | null }[];
};

function FulfillmentCard({
    orderId,
    fulfillment,
}: {
    orderId: number;
    fulfillment: FulfillmentSummary;
}) {
    const [status, setStatus] = useState(fulfillment.status);
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
                    {fulfillment.isPaid ? 'Paid' : 'Unpaid'}
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
