import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2, MapPin, Navigation, Phone, Package } from 'lucide-react';
import type { DriverAssignedOrder } from '@/components/driver/driver-order-types';
import { driverOrderStatusLabel } from '@/components/driver/driver-order-types';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { logout } from '@/routes';
import {
    index as ordersIndex,
    show as orderShow,
} from '@/routes/driver/orders';
import type { Auth } from '@/types';

type DriverDashboardSummary = {
    activeCount: number;
    deliveredCount: number;
    assignedCount: number;
    nextStop: DriverAssignedOrder | null;
    activeOrders: DriverAssignedOrder[];
};

type DriverDashboardProps = {
    summary: DriverDashboardSummary;
};

export default function DriverDashboard({ summary }: DriverDashboardProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;
    const firstName = user?.name.split(/\s+/u)[0] ?? 'there';
    const nextStop = summary.nextStop;
    const completedCount = summary.deliveredCount;
    const assignedCount = summary.assignedCount;
    const progressRatio =
        assignedCount > 0 ? completedCount / assignedCount : 0;

    const stats = [
        {
            icon: MapPin,
            label: 'Stops left',
            value: String(summary.activeCount),
            hint: `${assignedCount} assigned this shift`,
        },
        {
            icon: Package,
            label: 'Active orders',
            value: String(summary.activeCount),
            hint: 'Open the orders list',
        },
        {
            icon: CheckCircle2,
            label: 'Delivered',
            value: String(summary.deliveredCount),
            hint:
                assignedCount > 0
                    ? `${Math.round(progressRatio * 100)}% of assigned`
                    : 'None yet',
        },
    ] as const;

    return (
        <>
            <Head title="Driver Dashboard" />

            <div className="flex flex-col gap-8">
                <div className="space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <p className="text-foreground text-base">
                            Hello{' '}
                            <span className="font-semibold">{firstName}</span>{' '}
                            (Not {firstName}?{' '}
                            <Link
                                href={logout()}
                                as="button"
                                className="text-primary cursor-pointer font-medium underline underline-offset-2"
                            >
                                Log Out
                            </Link>
                            )
                        </p>
                        <DemoDataBadge />
                    </div>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                        From Your Driver Dashboard, You Can Check Your{' '}
                        <Link
                            href="/driver/activity"
                            prefetch
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Activity
                        </Link>
                        , Review Today&apos;s Route, And Track{' '}
                        <Link
                            href={ordersIndex.url()}
                            prefetch
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Active Orders
                        </Link>
                        .
                    </p>
                </div>

                {nextStop ? (
                    <Card className="gap-4 border-2 py-4 shadow-none">
                        <CardHeader className="gap-2 px-4">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge>Next stop</Badge>
                                <Badge variant="outline">
                                    {driverOrderStatusLabel[nextStop.status]}
                                </Badge>
                            </div>
                            <CardTitle className="text-lg leading-snug sm:text-xl">
                                {nextStop.customerName} ·{' '}
                                {nextStop.addressSummary}
                            </CardTitle>
                            <CardDescription className="text-sm">
                                {nextStop.windowLabel} · {nextStop.productCount}{' '}
                                {nextStop.productCount === 1 ? 'item' : 'items'}{' '}
                                · {nextStop.totalLabel}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="px-4">
                            <div className="bg-muted h-2 overflow-hidden rounded-full">
                                <div
                                    className="bg-primary h-full"
                                    style={{
                                        width: `${Math.min(100, Math.round(progressRatio * 100))}%`,
                                    }}
                                />
                            </div>
                            <p className="text-muted-foreground mt-2 text-xs">
                                {completedCount} of {assignedCount} stops
                                complete
                            </p>
                        </CardContent>
                        <CardFooter className="flex flex-col gap-2 px-4">
                            <Button
                                type="button"
                                size="lg"
                                className="h-12 w-full"
                                asChild
                            >
                                <Link
                                    href={orderShow.url(nextStop.id)}
                                    prefetch
                                >
                                    <Navigation data-icon="inline-start" />
                                    Open order
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                className="h-12 w-full"
                                asChild
                            >
                                <Link href={ordersIndex.url()} prefetch>
                                    <Phone data-icon="inline-start" />
                                    All orders
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
                ) : (
                    <Card className="gap-3 py-4 shadow-none">
                        <CardHeader className="gap-1 px-4">
                            <CardTitle className="text-base">
                                No active stops
                            </CardTitle>
                            <CardDescription>
                                All assigned demo orders are delivered, or none
                                are queued.
                            </CardDescription>
                        </CardHeader>
                        <CardFooter className="px-4">
                            <Button size="lg" className="h-12 w-full" asChild>
                                <Link href={ordersIndex.url()} prefetch>
                                    View orders
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
                )}

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {stats.map((stat) => {
                        const Icon = stat.icon;

                        return (
                            <div
                                key={stat.label}
                                className="border-border bg-background flex flex-col gap-2 border p-3 sm:p-4"
                            >
                                <div className="flex items-center gap-2">
                                    <span className="bg-muted flex size-8 items-center justify-center rounded-full">
                                        <Icon className="text-foreground size-4" />
                                    </span>
                                    <p className="text-muted-foreground text-xs font-medium">
                                        {stat.label}
                                    </p>
                                </div>
                                <p className="text-foreground text-xl font-semibold tracking-tight">
                                    {stat.value}
                                </p>
                                <p className="text-muted-foreground text-xs leading-snug">
                                    {stat.hint}
                                </p>
                            </div>
                        );
                    })}
                </div>

                <Card className="gap-3 py-4 shadow-none">
                    <CardHeader className="gap-1 px-4">
                        <CardTitle className="text-base">
                            Active orders
                        </CardTitle>
                        <CardDescription>
                            Next stops from your demo assignment list.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-0 px-4">
                        {summary.activeOrders.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No active orders right now.
                            </p>
                        ) : (
                            summary.activeOrders.map((order, index) => (
                                <div key={order.id}>
                                    {index > 0 ? (
                                        <Separator className="my-3" />
                                    ) : null}
                                    <Link
                                        href={orderShow.url(order.id)}
                                        prefetch
                                        className="flex items-start gap-3 py-0.5"
                                    >
                                        <div className="bg-secondary flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold">
                                            {index + 1}
                                        </div>
                                        <div className="min-w-0 flex-1 space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
                                                    {order.customerName}
                                                </p>
                                                <Badge
                                                    className="rounded-full"
                                                    variant={
                                                        index === 0
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {index === 0
                                                        ? 'Next'
                                                        : driverOrderStatusLabel[
                                                              order.status
                                                          ]}
                                                </Badge>
                                            </div>
                                            <p className="text-muted-foreground text-sm leading-snug">
                                                {order.addressSummary}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Window {order.windowLabel}
                                            </p>
                                        </div>
                                    </Link>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                <div>
                    <Button
                        type="button"
                        size="lg"
                        className="mb-2 h-12 w-full"
                        asChild
                    >
                        <Link href={ordersIndex.url()} prefetch>
                            View all orders
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="lg"
                        className="h-12 w-full"
                        asChild
                    >
                        <Link href="/driver/activity" prefetch>
                            My activity
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

DriverDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/driver/dashboard',
        },
    ],
};
