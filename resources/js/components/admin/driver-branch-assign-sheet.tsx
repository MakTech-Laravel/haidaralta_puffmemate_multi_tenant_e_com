import { router } from '@inertiajs/react';
import { Building2, Loader2, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { cn } from '@/lib/utils';

export type DriverBranchOption = {
    id: number;
    name: string;
    slug: string;
    code?: string | null;
    status?: boolean;
};

type BranchOptionsResponse = {
    data: DriverBranchOption[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

type DriverBranchAssignSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** When set, Confirm syncs via platform.drivers.branches.sync */
    driverId?: number | null;
    selectedIds: number[];
    onConfirm?: (
        branchIds: number[],
        branches: DriverBranchOption[],
    ) => void;
    title?: string;
    description?: string;
};

export function DriverBranchAssignSheet({
    open,
    onOpenChange,
    driverId = null,
    selectedIds,
    onConfirm,
    title = 'Manage branches',
    description = 'Search and assign branches this driver can serve.',
}: DriverBranchAssignSheetProps) {
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [activeOnly, setActiveOnly] = useState(true);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [options, setOptions] = useState<DriverBranchOption[]>([]);
    const [meta, setMeta] = useState<BranchOptionsResponse['meta'] | null>(
        null,
    );
    const [draftIds, setDraftIds] = useState<number[]>(selectedIds);
    const [knownBranches, setKnownBranches] = useState<
        Record<number, DriverBranchOption>
    >({});

    useEffect(() => {
        if (!open) {
            return;
        }

        setDraftIds(selectedIds);
        setPage(1);
        setSearch('');
        setDebouncedSearch('');
    }, [open, selectedIds]);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebouncedSearch(search.trim());
            setPage(1);
        }, 300);

        return () => window.clearTimeout(timer);
    }, [search]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();

        const load = async (): Promise<void> => {
            setLoading(true);

            try {
                const url = DriverController.branchOptions.url({
                    query: {
                        'filter[search]': debouncedSearch || undefined,
                        'filter[status]': activeOnly ? '1' : 'all',
                        page,
                        per_page: 20,
                    },
                });

                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Failed to load branches');
                }

                const payload = (await response.json()) as BranchOptionsResponse;
                setOptions(payload.data);
                setMeta(payload.meta);
                setKnownBranches((current) => {
                    const next = { ...current };

                    for (const branch of payload.data) {
                        next[branch.id] = branch;
                    }

                    return next;
                });
            } catch (error) {
                if ((error as Error).name === 'AbortError') {
                    return;
                }

                setOptions([]);
                setMeta(null);
            } finally {
                setLoading(false);
            }
        };

        void load();

        return () => controller.abort();
    }, [open, debouncedSearch, activeOnly, page]);

    const toggleBranch = (id: number): void => {
        setDraftIds((current) =>
            current.includes(id)
                ? current.filter((branchId) => branchId !== id)
                : [...current, id],
        );
    };

    const confirm = (): void => {
        if (driverId !== null && driverId !== undefined) {
            setSyncing(true);
            router.put(
                DriverController.syncBranches.url(driverId),
                { branch_ids: draftIds },
                {
                    preserveScroll: true,
                    onFinish: () => {
                        setSyncing(false);
                        onOpenChange(false);
                    },
                },
            );

            return;
        }

        const selected = draftIds.map(
            (id) =>
                knownBranches[id] ?? {
                    id,
                    name: `Branch #${id}`,
                    slug: '',
                },
        );
        onConfirm?.(draftIds, selected);
        onOpenChange(false);
    };

    const selectedLabels = draftIds
        .map((id) => knownBranches[id]?.name ?? `Branch #${id}`)
        .slice(0, 8);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-lg"
            >
                <SheetHeader className="border-b p-4 text-left">
                    <SheetTitle className="flex items-center gap-2">
                        <Building2 className="size-4" />
                        {title}
                    </SheetTitle>
                    <SheetDescription>{description}</SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-3 border-b p-4">
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search name, slug, or code…"
                            className="pl-8"
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="driver-branch-active-only"
                            checked={activeOnly}
                            onCheckedChange={(checked) => {
                                setActiveOnly(checked === true);
                                setPage(1);
                            }}
                        />
                        <Label htmlFor="driver-branch-active-only">
                            Active branches only
                        </Label>
                    </div>
                    {draftIds.length > 0 ? (
                        <div className="flex flex-wrap gap-1">
                            {selectedLabels.map((label) => (
                                <Badge key={label} variant="secondary">
                                    {label}
                                </Badge>
                            ))}
                            {draftIds.length > selectedLabels.length ? (
                                <Badge variant="outline">
                                    +{draftIds.length - selectedLabels.length}{' '}
                                    more
                                </Badge>
                            ) : null}
                        </div>
                    ) : (
                        <p className="text-muted-foreground text-sm">
                            No branches selected yet.
                        </p>
                    )}
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto p-2">
                    {loading ? (
                        <div className="text-muted-foreground flex items-center justify-center gap-2 py-12 text-sm">
                            <Loader2 className="size-4 animate-spin" />
                            Loading branches…
                        </div>
                    ) : options.length === 0 ? (
                        <div className="text-muted-foreground py-12 text-center text-sm">
                            No branches match this search.
                        </div>
                    ) : (
                        <ul className="space-y-1">
                            {options.map((branch) => {
                                const checked = draftIds.includes(branch.id);

                                return (
                                    <li key={branch.id}>
                                        <label
                                            className={cn(
                                                'hover:bg-muted/60 flex cursor-pointer items-start gap-3 rounded-md border px-3 py-2.5',
                                                checked &&
                                                    'border-primary/40 bg-muted/40',
                                            )}
                                        >
                                            <Checkbox
                                                checked={checked}
                                                onCheckedChange={() =>
                                                    toggleBranch(branch.id)
                                                }
                                                className="mt-0.5"
                                            />
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-medium">
                                                    {branch.name}
                                                </span>
                                                <span className="text-muted-foreground block truncate text-xs">
                                                    {branch.slug}
                                                    {branch.code
                                                        ? ` · ${branch.code}`
                                                        : ''}
                                                </span>
                                            </span>
                                            {branch.status === false ? (
                                                <Badge variant="outline">
                                                    Inactive
                                                </Badge>
                                            ) : null}
                                        </label>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>

                <SheetFooter className="border-t p-4 sm:flex-row sm:justify-between">
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={!meta || meta.current_page <= 1 || loading}
                            onClick={() => setPage((current) => current - 1)}
                        >
                            Previous
                        </Button>
                        <span className="text-muted-foreground text-xs">
                            {meta
                                ? `Page ${meta.current_page} of ${meta.last_page}`
                                : '—'}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={
                                !meta ||
                                meta.current_page >= meta.last_page ||
                                loading
                            }
                            onClick={() => setPage((current) => current + 1)}
                        >
                            Next
                        </Button>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            disabled={syncing}
                            onClick={confirm}
                        >
                            {syncing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : null}
                            Confirm ({draftIds.length})
                        </Button>
                    </div>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
