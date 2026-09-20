import { Link } from '@inertiajs/react';
import { Navigation } from 'lucide-react';
import type { DriverAssignedOrder } from '@/components/driver/driver-order-types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';

function pinPosition(index: number): { left: string; top: string } {
    const column = index % 4;
    const row = Math.floor(index / 4);

    return {
        left: `${14 + column * 22}%`,
        top: `${18 + row * 28 + (column % 2) * 6}%`,
    };
}

type DriverRouteMapPanelProps = {
    stops: DriverAssignedOrder[];
    nextStop: DriverAssignedOrder | null;
    highlightId?: string | null;
    areaLabel: string | null;
    navigateHref: string;
};

export function DriverRouteMapPanel({
    stops,
    nextStop,
    highlightId = null,
    areaLabel,
    navigateHref,
}: DriverRouteMapPanelProps) {
    const focusStop =
        stops.find((stop) => stop.id === highlightId) ?? nextStop ?? stops[0] ?? null;
    const visibleStops = stops.slice(0, 8);

    return (
        <section className="border-border overflow-hidden rounded-2xl border">
            <div
                className="relative isolate min-h-56 overflow-hidden bg-[#e8eee6] sm:min-h-72"
                data-test="driver-route-map"
            >
                <div
                    className="pointer-events-none absolute inset-0 opacity-70"
                    style={{
                        backgroundImage:
                            'linear-gradient(rgba(255,255,255,0.55) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.55) 1px, transparent 1px)',
                        backgroundSize: '28px 28px',
                    }}
                    aria-hidden
                />
                <svg
                    className="pointer-events-none absolute inset-0 size-full opacity-40"
                    viewBox="0 0 100 100"
                    preserveAspectRatio="none"
                    aria-hidden
                >
                    <path
                        d="M 8 82 C 22 58, 28 40, 46 36 C 64 32, 70 52, 88 24"
                        fill="none"
                        stroke="#3f4a3d"
                        strokeWidth="1.4"
                        strokeDasharray="3 2"
                    />
                </svg>
                <div className="bg-primary/10 pointer-events-none absolute -top-10 right-6 size-40 rounded-full blur-2xl" />
                <div className="bg-primary/15 pointer-events-none absolute bottom-4 left-8 size-28 rounded-full blur-xl" />

                <span className="bg-background/90 text-foreground absolute top-3 right-3 z-10 rounded-full px-2.5 py-1 text-xs font-medium shadow-sm">
                    You
                </span>

                {visibleStops.map((stop, index) => {
                    const isFocus = focusStop?.id === stop.id;
                    const position = pinPosition(index);

                    return (
                        <Link
                            key={stop.id}
                            href={orderShow.url(stop.id)}
                            prefetch
                            aria-label={`Open stop ${stop.customerName}`}
                            className="absolute z-10 -translate-x-1/2 -translate-y-full"
                            style={position}
                        >
                            <span
                                className={cn(
                                    'flex size-8 items-center justify-center rounded-full border-2 border-white text-xs font-semibold shadow-md',
                                    isFocus
                                        ? 'bg-primary text-primary-foreground size-10'
                                        : 'bg-foreground text-background',
                                )}
                            >
                                {index + 1}
                            </span>
                        </Link>
                    );
                })}

                {focusStop ? (
                    <div className="absolute inset-x-3 bottom-3 z-20 rounded-xl bg-white/95 p-3 shadow-lg sm:inset-x-auto sm:right-3 sm:left-auto sm:w-72">
                        <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            {highlightId === focusStop.id
                                ? 'Selected stop'
                                : 'Next stop'}
                        </p>
                        <p className="text-foreground mt-0.5 truncate font-semibold">
                            {focusStop.customerName}
                        </p>
                        <p className="text-muted-foreground truncate text-sm">
                            {focusStop.addressSummary}
                        </p>
                    </div>
                ) : null}
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div className="min-w-0">
                    <p className="text-foreground text-sm font-medium">
                        {areaLabel ?? "Today's run"}
                    </p>
                    <p className="text-muted-foreground text-sm">
                        {stops.length}{' '}
                        {stops.length === 1 ? 'drop' : 'drops'} on this map
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" asChild>
                    <a href={navigateHref} target="_blank" rel="noreferrer">
                        <Navigation data-icon="inline-start" />
                        Navigate
                    </a>
                </Button>
            </div>
        </section>
    );
}
