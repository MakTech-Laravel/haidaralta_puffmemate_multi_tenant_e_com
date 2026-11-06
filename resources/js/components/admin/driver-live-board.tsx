import { router } from '@inertiajs/react';
import { Maximize2, Radio, SignalLow, SignalZero } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { MapView } from '@/components/map/map-view';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDriverLocationsRealtime } from '@/hooks/use-driver-locations-realtime';
import type { DriverLocationEvent } from '@/hooks/use-driver-locations-realtime';
import { xsrfToken } from '@/lib/driver-location';
import type { LatLngTuple, MarkerSpec } from '@/lib/maps/types';
import { cn } from '@/lib/utils';

export type LiveDriver = {
    id: number;
    name: string;
    phone: string | null;
    avatar_url: string | null;
    vehicle_type: string | null;
    branch_ids: number[];
    position: {
        lat: number;
        lng: number;
        ts: number;
        heading: number | null;
        speed: number | null;
        accuracy: number | null;
    } | null;
    order: {
        number: string | null;
        status: string;
        status_label: string;
    } | null;
};

export type LiveBoard = {
    drivers: (LiveDriver & { signal: 'live' | 'stale' | 'none' })[];
    branches: { id: number; name: string }[];
    stale_after_seconds: number;
    off_duty_count: number;
};

type Props = {
    board: LiveBoard;
    channels: string[];
    watchUrl: string;
    /** Show the branch filter (platform map). */
    showBranchFilter?: boolean;
};

const WATCH_INTERVAL_MS = 30_000;
const TICK_MS = 15_000;
const SYDNEY: LatLngTuple = [-33.8688, 151.2093];

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function ago(seconds: number): string {
    if (seconds < 10) {
        return 'just now';
    }

    if (seconds < 60) {
        return `${Math.round(seconds)}s ago`;
    }

    if (seconds < 3600) {
        return `${Math.round(seconds / 60)} min ago`;
    }

    return `${Math.round(seconds / 3600)} h ago`;
}

/**
 * Live map of on-duty drivers. The first picture comes from the server snapshot (cache
 * only); after that every move arrives over Reverb and glides on the map, with no map or
 * address API calls. While this page is open it tells the server so positions are pushed.
 */
export function DriverLiveBoard({
    board,
    channels,
    watchUrl,
    showBranchFilter = false,
}: Props) {
    const [positions, setPositions] = useState<
        Record<number, NonNullable<LiveDriver['position']>>
    >({});
    const [now, setNow] = useState(() => Math.floor(Date.now() / 1000));
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [branchFilter, setBranchFilter] = useState<string>('all');
    const [fitKey, setFitKey] = useState(0);

    const reload = useCallback(() => {
        router.reload({ only: ['board'] });
    }, []);

    // A fresh snapshot supersedes socket positions older than it.
    useEffect(() => {
        setPositions((current) => {
            const next = { ...current };

            board.drivers.forEach((driver) => {
                const known = next[driver.id];

                if (
                    driver.position &&
                    (!known || known.ts <= driver.position.ts)
                ) {
                    next[driver.id] = driver.position;
                }
            });

            return next;
        });
    }, [board]);

    useDriverLocationsRealtime(channels, (event: DriverLocationEvent) => {
        if (!board.drivers.some((driver) => driver.id === event.driver_id)) {
            // A driver who just came on duty: pull a fresh snapshot.
            reload();

            return;
        }

        setPositions((current) => {
            const known = current[event.driver_id];

            if (known && known.ts >= event.ts) {
                return current;
            }

            return {
                ...current,
                [event.driver_id]: {
                    lat: event.lat,
                    lng: event.lng,
                    ts: event.ts,
                    heading: event.heading,
                    speed: event.speed,
                    accuracy: event.accuracy,
                },
            };
        });
    });

    // Tell the server a map is open (so it pushes positions) while the tab is visible.
    useEffect(() => {
        const ping = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            void fetch(watchUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            }).catch(() => undefined);
        };

        ping();

        const interval = window.setInterval(ping, WATCH_INTERVAL_MS);
        const onVisible = () => {
            if (document.visibilityState === 'visible') {
                ping();
                reload();
            }
        };
        const onReconnected = () => reload();

        document.addEventListener('visibilitychange', onVisible);
        window.addEventListener('echo:reconnected', onReconnected);

        return () => {
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', onVisible);
            window.removeEventListener('echo:reconnected', onReconnected);
        };
    }, [watchUrl, reload]);

    useEffect(() => {
        const interval = window.setInterval(
            () => setNow(Math.floor(Date.now() / 1000)),
            TICK_MS,
        );

        return () => window.clearInterval(interval);
    }, []);

    const drivers = useMemo(() => {
        const filterId = branchFilter === 'all' ? null : Number(branchFilter);

        return board.drivers
            .filter(
                (driver) =>
                    filterId === null || driver.branch_ids.includes(filterId),
            )
            .map((driver) => {
                const position = positions[driver.id] ?? driver.position;
                const signal: 'live' | 'stale' | 'none' = !position
                    ? 'none'
                    : now - position.ts <= board.stale_after_seconds
                      ? 'live'
                      : 'stale';

                return { ...driver, position, signal };
            });
    }, [board, positions, now, branchFilter]);

    const markers = useMemo<MarkerSpec[]>(
        () =>
            drivers.flatMap((driver) =>
                driver.position
                    ? [
                          {
                              id: `driver-${driver.id}`,
                              lat: driver.position.lat,
                              lng: driver.position.lng,
                              kind: 'driver' as const,
                              label: initials(driver.name),
                              muted: driver.signal !== 'live',
                              title: driver.name,
                              onClick: () => setSelectedId(driver.id),
                          },
                      ]
                    : [],
            ),
        [drivers],
    );

    const selected = drivers.find((driver) => driver.id === selectedId);
    const center: LatLngTuple = selected?.position
        ? [selected.position.lat, selected.position.lng]
        : markers[0]
          ? [markers[0].lat, markers[0].lng]
          : SYDNEY;
    const liveCount = drivers.filter(
        (driver) => driver.signal === 'live',
    ).length;
    const branchName = (id: number) =>
        board.branches.find((branch) => branch.id === id)?.name;

    // Fit once markers first exist, and whenever the visible set of drivers changes size.
    const markerCount = markers.length;

    useEffect(() => {
        setFitKey((key) => key + 1);
    }, [markerCount, branchFilter]);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-wrap items-center gap-3">
                <Badge variant="secondary" className="gap-1.5">
                    <Radio className="size-3" aria-hidden />
                    {liveCount} live
                </Badge>
                <Badge variant="outline">
                    {drivers.length - liveCount} no signal
                </Badge>
                <Badge variant="outline">{board.off_duty_count} off duty</Badge>

                <div className="ml-auto flex items-center gap-2">
                    {showBranchFilter && board.branches.length > 1 ? (
                        <Select
                            value={branchFilter}
                            onValueChange={setBranchFilter}
                        >
                            <SelectTrigger className="w-[13rem]">
                                <SelectValue placeholder="All branches" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All branches
                                </SelectItem>
                                {board.branches.map((branch) => (
                                    <SelectItem
                                        key={branch.id}
                                        value={String(branch.id)}
                                    >
                                        {branch.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ) : null}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            setSelectedId(null);
                            setFitKey((key) => key + 1);
                        }}
                    >
                        <Maximize2
                            className="size-4"
                            data-icon="inline-start"
                        />
                        Fit all
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 lg:grid-cols-[20rem_minmax(0,1fr)]">
                <ul
                    className="border-border/80 bg-card max-h-[70vh] divide-y overflow-y-auto rounded-2xl border"
                    data-test="driver-live-list"
                >
                    {drivers.length === 0 ? (
                        <li className="text-muted-foreground p-6 text-center text-sm">
                            No drivers are on duty right now.
                        </li>
                    ) : null}
                    {drivers.map((driver) => (
                        <li key={driver.id}>
                            <button
                                type="button"
                                onClick={() => setSelectedId(driver.id)}
                                className={cn(
                                    'hover:bg-muted/60 flex w-full items-start gap-3 p-3 text-left transition-colors',
                                    selectedId === driver.id && 'bg-muted',
                                )}
                            >
                                <span
                                    className={cn(
                                        'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white',
                                        driver.signal === 'live'
                                            ? 'bg-blue-600'
                                            : 'bg-gray-400',
                                    )}
                                >
                                    {initials(driver.name)}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {driver.name}
                                    </span>
                                    <span className="text-muted-foreground block truncate text-xs">
                                        {driver.signal === 'none'
                                            ? 'No signal yet'
                                            : `Seen ${ago(now - (driver.position?.ts ?? now))}`}
                                        {driver.order?.number
                                            ? ` · #${driver.order.number} ${driver.order.status_label}`
                                            : ' · Free'}
                                    </span>
                                    {showBranchFilter ? (
                                        <span className="text-muted-foreground block truncate text-xs">
                                            {driver.branch_ids
                                                .map(branchName)
                                                .filter(Boolean)
                                                .join(', ')}
                                        </span>
                                    ) : null}
                                </span>
                                {driver.signal ===
                                'live' ? null : driver.signal === 'stale' ? (
                                    <SignalLow
                                        className="text-amber-500 size-4 shrink-0"
                                        aria-label="Signal is old"
                                    />
                                ) : (
                                    <SignalZero
                                        className="text-muted-foreground size-4 shrink-0"
                                        aria-label="No signal"
                                    />
                                )}
                            </button>
                        </li>
                    ))}
                </ul>

                <MapView
                    center={center}
                    centerKey={
                        selected
                            ? `sel-${selected.id}-${selected.position?.ts}`
                            : undefined
                    }
                    markers={markers}
                    fitKey={fitKey}
                    className="border-border/80 h-[70vh] min-h-80 w-full overflow-hidden rounded-2xl border"
                />
            </div>
        </div>
    );
}
