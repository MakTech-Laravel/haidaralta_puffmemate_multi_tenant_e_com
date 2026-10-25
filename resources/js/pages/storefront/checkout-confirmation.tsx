import { Head, Link } from '@inertiajs/react';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { shop } from '@/routes';

type ConfirmationItem = {
    name: string;
    image: string;
    quantity: number;
    formattedLineTotal: string;
    isLateDelivery?: boolean;
};

type ConfirmationOrder = {
    orderNumber: string;
    itemCount: number;
    paymentMethod: string;
    deliveryMethod: string;
    deliveryDate: string | null;
    timeSlot: string | null;
    name: string;
    mobile: string;
    address: string;
    city: string | null;
    postalCode: string | null;
    couponCode: string | null;
    items: ConfirmationItem[];
    hasLateDeliveryItems?: boolean;
    subtotal: string;
    discount: string | null;
    deliveryFee: string;
    total: string;
    viewOrderUrl: string;
};

type ConfirmationProps = {
    order: ConfirmationOrder;
};

const sectionCardClass =
    'bg-accent gap-3 rounded-none border-none p-3 shadow-none sm:gap-4 sm:p-5';

function Detail({
    label,
    value,
}: {
    label: string;
    value: string | null | undefined;
}) {
    if (!value) {
        return null;
    }

    return (
        <div>
            <dt className="text-muted-foreground text-[10px] font-extrabold tracking-wider uppercase">
                {label}
            </dt>
            <dd className="text-foreground mt-1 text-sm font-semibold">
                {value}
            </dd>
        </div>
    );
}

export default function StorefrontCheckoutConfirmation({
    order,
}: ConfirmationProps) {
    const cityLine = [order.city, order.postalCode]
        .filter((value) => Boolean(value && value.trim() !== ''))
        .join(' ');

    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="flex-1">
                <div className="container mx-auto px-4 py-6 sm:px-6 sm:py-10">
                    <div className="max-w-2xl">
                        <h1 className="text-foreground text-3xl font-semibold tracking-tight sm:text-4xl">
                            Thank you.
                        </h1>
                        <p className="text-muted-foreground mt-2 text-base font-normal">
                            We received order {order.orderNumber}. Pay{' '}
                            {order.total} on delivery.
                        </p>
                        {order.hasLateDeliveryItems ? (
                            <p className="text-muted-foreground mt-3 text-sm">
                                Some items may arrive later while we collect
                                stock for your order.
                            </p>
                        ) : null}
                    </div>

                    <div className="mt-8 grid items-start gap-6 lg:grid-cols-[1fr_22rem] lg:gap-8">
                        <div className="flex flex-col gap-3 sm:gap-4">
                            <Card className={sectionCardClass}>
                                <CardHeader>
                                    <CardTitle className="text-foreground text-lg font-extrabold">
                                        Your details
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 px-0 sm:grid-cols-2">
                                    <Detail label="Full name" value={order.name} />
                                    <Detail
                                        label="Mobile number"
                                        value={order.mobile}
                                    />
                                </CardContent>
                            </Card>

                            <Card className={sectionCardClass}>
                                <CardHeader>
                                    <CardTitle className="text-foreground text-lg font-extrabold">
                                        Delivery
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4 px-0">
                                    <Detail label="Address" value={order.address} />
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Detail label="City" value={cityLine} />
                                        <Detail
                                            label="Method"
                                            value={order.deliveryMethod}
                                        />
                                    </div>
                                    {order.deliveryDate || order.timeSlot ? (
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <Detail
                                                label="Delivery date"
                                                value={order.deliveryDate}
                                            />
                                            <Detail
                                                label="Time slot"
                                                value={order.timeSlot}
                                            />
                                        </div>
                                    ) : null}
                                </CardContent>
                            </Card>

                            <Card className={sectionCardClass}>
                                <CardHeader>
                                    <CardTitle className="text-foreground text-lg font-extrabold">
                                        Payment at delivery
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 px-0">
                                    <p className="text-foreground text-sm font-semibold">
                                        {order.paymentMethod}
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        Have {order.total} ready when the
                                        driver arrives.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <aside className="bg-accent h-fit p-5 lg:sticky lg:top-28">
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-foreground text-2xl font-bold tracking-tight">
                                    Your order
                                </h2>
                                <span className="text-muted-foreground text-xs font-extrabold tracking-wider uppercase">
                                    {order.itemCount} item
                                    {order.itemCount === 1 ? '' : 's'}
                                </span>
                            </div>

                            <ul className="border-border mt-5 max-h-56 space-y-4 overflow-y-auto overscroll-contain border-y py-4 pr-1">
                                {order.items.map((item, index) => (
                                    <li
                                        key={`${item.name}-${index}`}
                                        className="flex items-center gap-3"
                                    >
                                        <div className="bg-muted size-12 shrink-0 overflow-hidden rounded-md">
                                            <StorefrontProductImage
                                                src={item.image}
                                                alt={item.name}
                                            />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-foreground truncate text-sm font-semibold">
                                                {item.name}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Quantity x {item.quantity}
                                                {item.isLateDelivery
                                                    ? ' · May arrive later'
                                                    : ''}
                                            </p>
                                        </div>
                                        <p className="text-foreground shrink-0 text-sm font-bold">
                                            {item.formattedLineTotal}
                                        </p>
                                    </li>
                                ))}
                            </ul>

                            <dl className="mt-5 space-y-2.5">
                                <div className="flex items-center justify-between gap-3 text-sm">
                                    <dt className="text-muted-foreground">
                                        Subtotal
                                    </dt>
                                    <dd className="text-foreground font-semibold">
                                        {order.subtotal}
                                    </dd>
                                </div>
                                {order.discount ? (
                                    <div className="flex items-center justify-between gap-3 text-sm">
                                        <dt className="text-muted-foreground">
                                            {order.couponCode ?? 'Discount'}
                                        </dt>
                                        <dd className="text-foreground font-semibold">
                                            {order.discount}
                                        </dd>
                                    </div>
                                ) : null}
                                <div className="flex items-center justify-between gap-3 text-sm">
                                    <dt className="text-muted-foreground">
                                        Delivery
                                    </dt>
                                    <dd className="text-foreground font-bold">
                                        {order.deliveryFee}
                                    </dd>
                                </div>
                            </dl>

                            <div className="border-border mt-4 flex items-end justify-between gap-3 border-t pt-4">
                                <span className="text-foreground text-base font-bold">
                                    Total
                                </span>
                                <span className="text-foreground text-2xl font-bold">
                                    {order.total}
                                </span>
                            </div>

                            <Button
                                asChild
                                size="lg"
                                className="mt-5 h-12 w-full rounded-none text-sm font-bold tracking-wide uppercase"
                            >
                                <Link href={shop.url()}>Continue shopping</Link>
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                size="lg"
                                className="mt-2 h-12 w-full rounded-none text-sm font-bold tracking-wide uppercase"
                            >
                                <Link href={order.viewOrderUrl}>
                                    View order
                                </Link>
                            </Button>
                        </aside>
                    </div>
                </div>
            </div>
        </>
    );
}
