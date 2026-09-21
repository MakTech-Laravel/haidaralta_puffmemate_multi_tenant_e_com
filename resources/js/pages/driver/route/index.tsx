import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, MapPin, Navigation, Package } from 'lucide-react';
import { useEffect } from 'react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import {
    driverMapsQuery,
    driverOrderStatusLabel,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { DriverRouteMapPanel } from '@/components/driver/driver-route-map-panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';
import { index as routeIndex } from '@/routes/driver/route';

function mapsSearchUrl(query: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
}

type DriverRouteProps = {
    remaining: DriverAssignedOrder[];
    completed: DriverAssignedOrder[];
    nextStop: DriverAssignedOrder | null;
    highlightId?: string | null;
};

export default function DriverRouteIndex({
    remaining,
    completed,
    nextStop,
    highlightId = null,
}: DriverRouteProps) {
    const assignedCount = remaining.length + completed.length;
    const progress =
        assignedCount === 0
            ? 0
            : Math.round((completed.length / assignedCount) * 100);
    const mapStops = remaining.length > 0 ? remaining : completed;
    const highlightedStop =
        remaining.find((order) => order.id === highlightId) ??
        completed.find((order) => order.id === highlightId) ??
        null;

    useEffect(() => {
        if (!highlightId) {
            return;
        }

        document
            .getElementById(`driver-stop-${highlightId}`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, [highlightId]);

    return (
        <>
            <Head title="Today's route" />

            <div className="flex flex-col gap-6">
                <div className="space-y-3">
                    <div className="space-y-1">
                        <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                            Route
                        </h1>
                        <p className="text-muted-foreground text-base">
                            {remaining.length} left · {completed.length} done ·{' '}
                            {assignedCount} assigned
                        </p>
                    </div>
                    <Progress value={progress} className="h-2" />
                </div>

                <DriverRouteMapPanel
                    stops={mapStops}
                    nextStop={nextStop}
                    highlightId={highlightId}
                    areaLabel="Assigned stops"
                    navigateHref={mapsSearchUrl(
                        driverMapsQuery(
                            nextStop?.addressSummary ??
                                mapStops[0]?.addressSummary ??
                                '',
                        ),
                    )}
                />

                {highlightedStop ? (
                    <div
                        className="border-primary bg-primary/5 flex items-start gap-3 rounded-xl border px-4 py-3"
                        data-test="driver-route-highlight"
                    >
                        <MapPin className="text-primary mt-0.5 size-5 shrink-0" />
                        <div className="min-w-0 space-y-0.5">
                            <p className="text-foreground font-medium">
                                Selected · {highlightedStop.customerName}
                            </p>
                            <p className="text-muted-foreground text-sm leading-snug">
                                {highlightedStop.addressSummary}
                                {highlightedStop.unitLabel
                                    ? ` · ${highlightedStop.unitLabel}`
                                    : ''}
                            </p>
                        </div>
                    </div>
                ) : null}

                {nextStop ? (
                    <section className="border-border bg-card space-y-3 rounded-2xl border p-4 sm:p-5">
                        <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            Next stop
                        </p>
                        <div className="space-y-1">
                            <h2 className="text-foreground text-xl font-semibold tracking-tight">
                                {nextStop.customerName}
                            </h2>
                            <p className="text-muted-foreground text-base">
                                {nextStop.addressSummary}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {nextStop.windowLabel} · {nextStop.totalLabel}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="lg"
                            className="h-12 w-full"
                            asChild
                        >
                            <Link href={orderShow.url(nextStop.id)} prefetch>
                                <Navigation data-icon="inline-start" />
                                Start this stop
                            </Link>
                        </Button>
                    </section>
                ) : null}

                {remaining.length === 0 ? (
                    <DriverEmptyState
                        icon={Package}
                        title="Route complete"
                        description="All assigned drops for this run are delivered."
                    />
                ) : (
                    <section className="flex flex-col gap-3">
                        <h2 className="text-foreground text-lg font-semibold">
                            Remaining
                        </h2>
                        <ol className="flex flex-col gap-3">
                            {remaining.map((order, index) => {
                                const isNext = nextStop?.id === order.id;

                                return (
                                    <li
                                        key={order.id}
                                        id={`driver-stop-${order.id}`}
                                    >
                                        <Link
                                            href={orderShow.url(order.id)}
                                            prefetch
                                            className={cn(
                                                'bg-accent flex items-start gap-3 rounded-xl p-4 transition-opacity hover:opacity-95 sm:p-5',
                                                (isNext ||
                                                    highlightId === order.id) &&
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
                                                        {order.customerName}
                                                    </span>
                                                    {isNext ? (
                                                        <Badge>Next</Badge>
                                                    ) : null}
                                                    {highlightId ===
                                                    order.id ? (
                                                        <Badge>
                                                            Selected
                                                        </Badge>
                                                    ) : null}
                                                    <Badge
                                                        variant="outline"
                                                        className="font-medium"
                                                    >
                                                        {
                                                            driverOrderStatusLabel[
                                                                order.status
                                                            ]
                                                        }
                                                    </Badge>
                                                </span>
                                                <span className="text-muted-foreground block text-base leading-snug">
                                                    {order.addressSummary}
                                                </span>
                                                <span className="text-muted-foreground block text-sm">
                                                    {order.windowLabel} ·{' '}
                                                    {order.productCount}{' '}
                                                    {order.productCount === 1
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
                )}

                {completed.length > 0 ? (
                    <section className="space-y-3">
                        <h2 className="text-foreground text-lg font-semibold">
                            Completed
                        </h2>
                        <ul className="flex flex-col gap-2">
                            {completed.map((order) => (
                                <li
                                    key={order.id}
                                    id={`driver-stop-${order.id}`}
                                >
                                    <Link
                                        href={orderShow.url(order.id)}
                                        prefetch
                                        className={cn(
                                            'text-muted-foreground hover:text-foreground flex items-start gap-3 rounded-xl border px-4 py-3 text-base transition-colors',
                                            highlightId === order.id &&
                                                'ring-primary text-foreground ring-2',
                                        )}
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
