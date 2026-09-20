import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, MapPin, Navigation } from 'lucide-react';
import { OrderTrackMap } from '@/components/customer/order-track-map';
import {
    driverAreaMapsQuery,
    driverOrderStatusLabel,
    type DriverAssignedArea,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';
import { index as routeIndex } from '@/routes/driver/route';

function mapsSearchUrl(query: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
}

type DriverRouteProps = {
    areas: DriverAssignedArea[];
    remaining: DriverAssignedOrder[];
    completed: DriverAssignedOrder[];
    nextStop: DriverAssignedOrder | null;
    currentArea: DriverAssignedArea | null;
};

export default function DriverRouteIndex({
    areas,
    remaining,
    completed,
    nextStop,
    currentArea,
}: DriverRouteProps) {
    const remainingAreas = areas.filter((area) => area.remainingCount > 0);
    const completedAreas = areas.filter((area) => area.deliveredCount > 0);

    return (
        <>
            <Head title="Today's route" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                        <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                            Today&apos;s route
                        </h1>
                        <p className="text-muted-foreground text-base">
                            {remaining.length}{' '}
                            {remaining.length === 1 ? 'stop' : 'stops'} left
                            across {remainingAreas.length}{' '}
                            {remainingAreas.length === 1 ? 'area' : 'areas'}
                            {completed.length > 0
                                ? ` · ${completed.length} done`
                                : ''}
                        </p>
                    </div>
                    <DemoDataBadge />
                </div>

                <OrderTrackMap />

                {remaining.length === 0 ? (
                    <p className="text-muted-foreground text-base">
                        No remaining stops on this run.
                    </p>
                ) : (
                    remainingAreas.map((area) => {
                        const isCurrent = currentArea?.id === area.id;

                        return (
                            <section
                                key={area.id}
                                className="flex flex-col gap-3"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="space-y-0.5">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="text-foreground text-lg font-semibold">
                                                {area.label}
                                            </h2>
                                            {isCurrent ? (
                                                <Badge>Current area</Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Later
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {area.remainingCount} of{' '}
                                            {area.orderCount} still to deliver
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        asChild
                                    >
                                        <a
                                            href={mapsSearchUrl(
                                                driverAreaMapsQuery(area.label),
                                            )}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            <MapPin data-icon="inline-start" />
                                            Navigate to area
                                        </a>
                                    </Button>
                                </div>
                                <ol className="flex flex-col gap-3">
                                    {area.remaining.map((order, index) => {
                                        const isNext =
                                            nextStop?.id === order.id;

                                        return (
                                            <li key={order.id}>
                                                <Link
                                                    href={orderShow.url(
                                                        order.id,
                                                    )}
                                                    prefetch
                                                    className={cn(
                                                        'bg-accent flex items-start gap-3 rounded-xl p-4 transition-opacity hover:opacity-95 sm:p-5',
                                                        isNext &&
                                                            'ring-primary ring-2',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                                            isNext
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'bg-muted text-foreground',
                                                        )}
                                                    >
                                                        {index + 1}
                                                    </span>
                                                    <span className="min-w-0 flex-1 space-y-1">
                                                        <span className="flex flex-wrap items-center gap-2">
                                                            <span className="text-foreground text-lg font-semibold tracking-tight">
                                                                {
                                                                    order.customerName
                                                                }
                                                            </span>
                                                            {isNext ? (
                                                                <Badge>
                                                                    Next
                                                                </Badge>
                                                            ) : null}
                                                            <Badge
                                                                variant="outline"
                                                                className="font-medium"
                                                            >
                                                                {
                                                                    driverOrderStatusLabel[
                                                                        order
                                                                            .status
                                                                    ]
                                                                }
                                                            </Badge>
                                                        </span>
                                                        <span className="text-muted-foreground block text-base leading-snug">
                                                            {order.addressSummary}
                                                        </span>
                                                        <span className="text-muted-foreground block text-sm">
                                                            {order.windowLabel}{' '}
                                                            ·{' '}
                                                            {order.productCount}{' '}
                                                            {order.productCount ===
                                                            1
                                                                ? 'item'
                                                                : 'items'}{' '}
                                                            · {order.totalLabel}
                                                        </span>
                                                    </span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ol>
                            </section>
                        );
                    })
                )}

                {nextStop ? (
                    <Button
                        type="button"
                        size="lg"
                        className="h-12 w-full"
                        asChild
                    >
                        <Link href={orderShow.url(nextStop.id)} prefetch>
                            <Navigation data-icon="inline-start" />
                            Open next stop
                        </Link>
                    </Button>
                ) : null}

                {completedAreas.length > 0 ? (
                    <section className="space-y-4">
                        <h2 className="text-foreground text-lg font-semibold">
                            Completed
                        </h2>
                        {completedAreas.map((area) => (
                            <div key={area.id} className="space-y-2">
                                <p className="text-muted-foreground text-sm font-medium">
                                    {area.label}
                                </p>
                                <ul className="flex flex-col gap-2">
                                    {area.delivered.map((order) => (
                                        <li key={order.id}>
                                            <Link
                                                href={orderShow.url(order.id)}
                                                prefetch
                                                className="text-muted-foreground hover:text-foreground flex items-start gap-3 rounded-xl border px-4 py-3 text-base transition-colors"
                                            >
                                                <CheckCircle2
                                                    className="mt-0.5 size-5 shrink-0"
                                                    aria-hidden
                                                />
                                                <span className="min-w-0">
                                                    <span className="text-foreground block font-medium">
                                                        {order.customerName}
                                                    </span>
                                                    <span className="block text-sm">
                                                        {order.addressSummary}
                                                    </span>
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </section>
                ) : null}
            </div>
        </>
    );
}

DriverRouteIndex.layout = {
    breadcrumbs: [
        {
            title: 'Route',
            href: routeIndex.url(),
        },
    ],
};
