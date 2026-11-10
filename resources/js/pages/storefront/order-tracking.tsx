import { Head, Link } from "@inertiajs/react";
import { OrderTrackAddress } from "@/components/customer/order-track-address";
import { OrderTrackProducts } from "@/components/customer/order-track-products";
import type {
    OrderTimelineStep,
    OrderTrackProduct,
    OrderTrackShippingAddress,
} from "@/components/customer/order-track-types";
import { OrderStatusProgress } from "@/components/orders/order-status-progress";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { shop } from "@/routes";

type PublicOrder = {
    orderNumber: string;
    status: string;
    statusLabel: string;
    paymentStatusLabel: string;
    date: string;
    deliveryDate: string | null;
    timeSlot: string | null;
    products: OrderTrackProduct[];
    subtotalLabel: string;
    discountLabel: string | null;
    couponCode: string | null;
    deliveryFeeLabel: string;
    totalLabel: string;
    shippingAddress: OrderTrackShippingAddress;
    timeline: OrderTimelineStep[];
    timelinePrevious: OrderTimelineStep[][];
};

type OrderTrackingProps = {
    order: PublicOrder;
};

export default function StorefrontOrderTracking({ order }: OrderTrackingProps) {
    const schedule = [order.deliveryDate, order.timeSlot]
        .filter((value): value is string => Boolean(value))
        .join(" · ");
    const finished =
        order.status === "delivered" || order.status === "cancelled";

    return (
        <>
            <Head title={`Order ${order.orderNumber}`}>
                <meta name="robots" content="noindex" />
            </Head>

            <div className="flex-1">
                <div className="container mx-auto px-4 py-6 sm:px-6 sm:py-10">
                    <div className="bg-accent space-y-3 p-4 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h1 className="text-foreground text-2xl font-semibold tracking-tight sm:text-3xl">
                                    Order {order.orderNumber}
                                </h1>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    Placed on {order.date} ·{" "}
                                    {order.paymentStatusLabel}
                                </p>
                                {schedule ? (
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Delivery: {schedule}
                                    </p>
                                ) : null}
                            </div>
                            <Badge
                                className={cn(
                                    "rounded-sm px-2.5 py-0.5 text-sm font-medium",
                                    finished
                                        ? "bg-muted-foreground/15 text-foreground border-transparent"
                                        : "bg-primary text-primary-foreground border-transparent",
                                )}
                            >
                                {order.statusLabel}
                            </Badge>
                        </div>
                    </div>

                    <div className="mt-5 grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                        <div className="flex min-w-0 flex-col gap-5">
                            <OrderTrackProducts
                                products={order.products}
                                subtotalLabel={order.subtotalLabel}
                                discountLabel={order.discountLabel}
                                couponCode={order.couponCode}
                                deliveryFeeLabel={order.deliveryFeeLabel}
                                totalLabel={order.totalLabel}
                            />
                            <Card className="border-border/70 shadow-sm">
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-base">
                                        Order progress
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <OrderStatusProgress
                                        steps={order.timeline}
                                        previousAttempts={
                                            order.timelinePrevious
                                        }
                                        showActivity={false}
                                        previousAttemptsLayout="sheet"
                                    />
                                </CardContent>
                            </Card>
                        </div>

                        <aside className="flex h-fit flex-col gap-5">
                            <Card className="border-border/70 shadow-sm">
                                <OrderTrackAddress
                                    address={order.shippingAddress}
                                />

                                <Button
                                    asChild
                                    size="lg"
                                    className=" mx-auto max-w-[200px]  rounded-none text-sm font-bold tracking-wide uppercase"
                                >
                                    <Link href={shop.url()}>
                                        Continue shopping
                                    </Link>
                                </Button>
                            </Card>
                        </aside>
                    </div>
                </div>
            </div>
        </>
    );
}
