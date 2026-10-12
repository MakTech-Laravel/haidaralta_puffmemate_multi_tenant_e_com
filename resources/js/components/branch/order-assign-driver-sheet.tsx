import { router } from '@inertiajs/react';
import { Loader2, Search, UserRound } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import OrderAssignController from '@/actions/App/Http/Controllers/Branch/OrderAssignController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export type OrderAssignDriverOption = {
    id: number;
    name: string;
    phone: string;
    avatar_url: string | null;
};

type CandidatesMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

type CandidatesResponse = {
    data: OrderAssignDriverOption[];
    meta: CandidatesMeta;
    current_driver_id: number | null;
};

type OrderAssignDriverSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    fulfillmentId: number | null;
    fulfillmentNumber?: string | null;
    mode?: 'assign' | 'change';
    /** Branch portal: branch slug + fulfillment for Wayfinder defaults */
    branchSlug?: string;
    candidatesUrl?: string | null;
    storeUrl?: string | null;
};

const PAGE_SIZE = 20;
const REASON_MAX = 255;

function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/u).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
}

function mergeDrivers(
    current: OrderAssignDriverOption[],
    incoming: OrderAssignDriverOption[],
    replace: boolean,
): OrderAssignDriverOption[] {
    const source = replace ? incoming : [...current, ...incoming];
    const seen = new Set<number>();

    return source.filter((driver) => {
        if (seen.has(driver.id)) {
            return false;
        }

        seen.add(driver.id);

        return true;
    });
}

export function OrderAssignDriverSheet({
    open,
    onOpenChange,
    branchSlug,
    fulfillmentId,
    fulfillmentNumber = null,
    mode = 'assign',
    candidatesUrl = null,
    storeUrl = null,
}: OrderAssignDriverSheetProps) {
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [assigning, setAssigning] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [drivers, setDrivers] = useState<OrderAssignDriverOption[]>([]);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [currentDriverId, setCurrentDriverId] = useState<number | null>(null);
    const [reason, setReason] = useState('');
    const [meta, setMeta] = useState<CandidatesMeta | null>(null);

    const pageRef = useRef(1);
    const lastPageRef = useRef(1);
    const loadingRef = useRef(false);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        setSearch('');
        setDebouncedSearch('');
        setDrivers([]);
        setSelectedId(null);
        setCurrentDriverId(null);
        setReason('');
        setError(null);
        setMeta(null);
        pageRef.current = 1;
        lastPageRef.current = 1;
    }, [open, fulfillmentId]);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebouncedSearch(search.trim());
        }, 300);

        return () => window.clearTimeout(timer);
    }, [search]);

    const loadPage = async (
        nextPage: number,
        nextSearch: string,
        replace: boolean,
        signal: AbortSignal,
    ): Promise<void> => {
        if (fulfillmentId === null || loadingRef.current) {
            return;
        }

        loadingRef.current = true;

        if (replace) {
            setLoading(true);
            setError(null);
        } else {
            setLoadingMore(true);
        }

        try {
            const url =
                candidatesUrl ??
                (branchSlug
                    ? OrderAssignController.candidates.url(
                          [branchSlug, fulfillmentId],
                          {
                              query: {
                                  'filter[search]': nextSearch || undefined,
                                  page: nextPage,
                                  per_page: PAGE_SIZE,
                              },
                          },
                      )
                    : null);

            if (!url) {
                throw new Error('Unable to load drivers.');
            }

            const response = await fetch(
                candidatesUrl
                    ? `${url}${url.includes('?') ? '&' : '?'}${new URLSearchParams({
                          ...(nextSearch ? { 'filter[search]': nextSearch } : {}),
                          page: String(nextPage),
                          per_page: String(PAGE_SIZE),
                      }).toString()}`
                    : url,
                {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(
                    'Cannot assign a driver after the order is delivered.',
                );
            }

            const payload = (await response.json()) as CandidatesResponse;

            if (signal.aborted) {
                return;
            }

            setDrivers((current) =>
                mergeDrivers(current, payload.data, replace),
            );
            setMeta(payload.meta);
            pageRef.current = payload.meta.current_page;
            lastPageRef.current = payload.meta.last_page;

            if (replace) {
                setSelectedId(payload.current_driver_id);
                setCurrentDriverId(payload.current_driver_id);
                setReason('');
            }
        } catch (loadError) {
            if ((loadError as Error).name === 'AbortError' || signal.aborted) {
                return;
            }

                if (replace) {
                    setDrivers([]);
                    setMeta(null);
                    setSelectedId(null);
                    setCurrentDriverId(null);
                }

            setError(
                loadError instanceof Error
                    ? loadError.message
                    : 'Unable to load drivers.',
            );
        } finally {
            loadingRef.current = false;

            if (!signal.aborted) {
                setLoading(false);
                setLoadingMore(false);
            }
        }
    };

    useEffect(() => {
        if (!open || fulfillmentId === null) {
            return;
        }

        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;
        loadingRef.current = false;

        void loadPage(1, debouncedSearch, true, controller.signal);

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional page-1 reload on search/open
    }, [open, branchSlug, fulfillmentId, debouncedSearch, candidatesUrl]);

    const handleScroll = (
        event: React.UIEvent<HTMLDivElement>,
    ): void => {
        const target = event.currentTarget;

        if (
            loadingRef.current ||
            pageRef.current >= lastPageRef.current ||
            target.scrollTop + target.clientHeight < target.scrollHeight - 48
        ) {
            return;
        }

        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        void loadPage(
            pageRef.current + 1,
            debouncedSearch,
            false,
            controller.signal,
        );
    };

    const isReassign = currentDriverId !== null;
    const sheetMode = mode === 'change' || isReassign ? 'change' : 'assign';
    const sheetTitle =
        sheetMode === 'change' ? 'Change driver' : 'Assign driver';
    const sheetDescription = fulfillmentNumber
        ? sheetMode === 'change'
            ? `Choose a different driver for ${fulfillmentNumber}.`
            : `Choose a driver for ${fulfillmentNumber}.`
        : sheetMode === 'change'
          ? 'Choose a different active driver from this branch.'
          : 'Choose an active driver from this branch.';
    const submitLabel =
        sheetMode === 'change' ? 'Change driver' : 'Assign driver';

    const submit = (advance?: 'picked_up' | 'out_for_delivery'): void => {
        if (fulfillmentId === null || selectedId === null) {
            return;
        }

        if (isReassign && reason.trim() === '') {
            return;
        }

        setAssigning(true);

        const actionUrl =
            storeUrl ??
            (branchSlug
                ? OrderAssignController.store.url([branchSlug, fulfillmentId])
                : null);

        if (!actionUrl) {
            setAssigning(false);

            return;
        }

        router.post(
            actionUrl,
            {
                driver_id: selectedId,
                ...(isReassign ? { reason: reason.trim() } : {}),
                ...(advance ? { advance } : {}),
            },
            {
                preserveScroll: true,
                onFinish: () => setAssigning(false),
                onSuccess: () => onOpenChange(false),
            },
        );
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="flex w-full flex-col gap-0 sm:max-w-md">
                <SheetHeader className="border-b p-4 text-left">
                    <SheetTitle className="flex items-center gap-2">
                        <UserRound className="size-4" />
                        {sheetTitle}
                    </SheetTitle>
                    <SheetDescription>{sheetDescription}</SheetDescription>
                </SheetHeader>

                <div className="border-b p-4">
                    <div className="relative">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by name or phone"
                            className="pl-9"
                            autoComplete="off"
                        />
                    </div>
                </div>

                <div
                    className="min-h-0 flex-1 overflow-y-auto p-4"
                    onScroll={handleScroll}
                >
                    {loading ? (
                        <div className="text-muted-foreground flex items-center justify-center gap-2 py-10 text-sm">
                            <Loader2 className="size-4 animate-spin" />
                            Loading drivers…
                        </div>
                    ) : null}

                    {!loading && error ? (
                        <p className="text-destructive text-sm">{error}</p>
                    ) : null}

                    {!loading && !error && drivers.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {debouncedSearch
                                ? 'No drivers match that search.'
                                : 'No active drivers are available for this branch.'}
                        </p>
                    ) : null}

                    {!loading && !error && drivers.length > 0 ? (
                        <ul className="space-y-2">
                            {drivers.map((driver) => {
                                const selected = selectedId === driver.id;

                                return (
                                    <li key={driver.id}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setSelectedId(driver.id)
                                            }
                                            className={cn(
                                                'hover:bg-muted/60 flex w-full items-center gap-3 rounded-lg border p-3 text-left transition-colors',
                                                selected
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border',
                                            )}
                                        >
                                            <Avatar className="size-11">
                                                {driver.avatar_url ? (
                                                    <AvatarImage
                                                        src={driver.avatar_url}
                                                        alt=""
                                                    />
                                                ) : null}
                                                <AvatarFallback>
                                                    {initialsFromName(
                                                        driver.name,
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate font-medium">
                                                    {driver.name}
                                                </span>
                                                <span className="text-muted-foreground block truncate text-sm">
                                                    {driver.phone}
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    ) : null}

                    {loadingMore ? (
                        <div className="text-muted-foreground flex items-center justify-center gap-2 py-4 text-sm">
                            <Loader2 className="size-4 animate-spin" />
                            Loading more…
                        </div>
                    ) : null}

                    {!loading &&
                    !loadingMore &&
                    !error &&
                    meta !== null &&
                    meta.current_page >= meta.last_page &&
                    drivers.length > 0 ? (
                        <p className="text-muted-foreground pt-3 text-center text-xs">
                            {meta.total} driver{meta.total === 1 ? '' : 's'}
                        </p>
                    ) : null}

                    {isReassign ? (
                        <div className="mt-4 space-y-2">
                            <Label htmlFor="sheet-assign-reason">
                                Reason for change{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Textarea
                                id="sheet-assign-reason"
                                value={reason}
                                onChange={(event) =>
                                    setReason(
                                        event.target.value.slice(0, REASON_MAX),
                                    )
                                }
                                placeholder="Why are you changing the driver?"
                                rows={3}
                                maxLength={REASON_MAX}
                                required
                                aria-required
                            />
                            <p className="text-muted-foreground text-xs">
                                Required · {reason.length}/{REASON_MAX}
                            </p>
                        </div>
                    ) : null}
                </div>

                <SheetFooter className="border-t p-4 sm:flex-row sm:justify-between">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={assigning}
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <div className="flex flex-col gap-2">
                        {(
                            [
                                { label: submitLabel, advance: undefined },
                                {
                                    label: 'Assign and picked up',
                                    advance: 'picked_up' as const,
                                },
                                {
                                    label: 'Assign, picked up, and on the way',
                                    advance: 'out_for_delivery' as const,
                                },
                            ] as const
                        ).map((action) => (
                            <Button
                                key={action.label}
                                type="button"
                                variant={action.advance ? 'outline' : 'default'}
                                disabled={
                                    assigning ||
                                    loading ||
                                    selectedId === null ||
                                    Boolean(error) ||
                                    (isReassign && reason.trim() === '')
                                }
                                onClick={() => submit(action.advance)}
                            >
                                {assigning ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : null}
                                {action.label}
                            </Button>
                        ))}
                    </div>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
