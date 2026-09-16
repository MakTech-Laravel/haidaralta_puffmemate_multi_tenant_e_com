import { Check, ChevronsUpDown, Loader2, XIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { fetchStockOptions } from '@/lib/stock-query';
import { cn } from '@/lib/utils';

export type MultiComboboxOption = {
    value: string;
    label: string;
    description?: string | null;
};

export function InfiniteScrollMultiCombobox({
    optionsUrl,
    values,
    selectedItems,
    onToggle,
    placeholder,
    searchPlaceholder,
    emptyText,
    className,
    id,
    descriptionKey = 'sku',
    showSelectedBadges = true,
}: {
    optionsUrl: string;
    values: string[];
    selectedItems: MultiComboboxOption[];
    onToggle: (option: MultiComboboxOption, selected: boolean) => void;
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
    className?: string;
    id?: string;
    descriptionKey?: 'sku' | null;
    /** Form pickers keep chips; compact toolbars should pass false. */
    showSelectedBadges?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [options, setOptions] = useState<MultiComboboxOption[]>([]);
    const [loading, setLoading] = useState(false);
    const loadingRef = useRef(false);
    const pageRef = useRef(1);
    const lastPageRef = useRef(1);
    const controllerRef = useRef<AbortController | null>(null);
    const selected = new Set(values);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebouncedSearch(search.trim());
        }, 300);

        return () => window.clearTimeout(timer);
    }, [search]);

    useEffect(() => {
        if (!open) {
            return;
        }

        controllerRef.current?.abort();
        const controller = new AbortController();
        controllerRef.current = controller;

        pageRef.current = 1;
        lastPageRef.current = 1;
        void loadPage(1, debouncedSearch, true, controller.signal);

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, debouncedSearch, optionsUrl]);

    const loadPage = async (
        nextPage: number,
        nextSearch: string,
        replace: boolean,
        signal?: AbortSignal,
    ): Promise<void> => {
        if (loadingRef.current && !replace) {
            return;
        }

        loadingRef.current = true;
        setLoading(true);

        try {
            const payload = await fetchStockOptions(optionsUrl, {
                search: nextSearch,
                page: nextPage,
                perPage: 20,
                signal,
            });

            if (signal?.aborted) {
                return;
            }

            const mapped = payload.data.map((row) => ({
                value: String(row.id),
                label: row.name,
                description:
                    descriptionKey === 'sku' ? (row.sku ?? null) : null,
            }));

            setOptions((current) => {
                const source = replace ? mapped : [...current, ...mapped];
                const seen = new Set<string>();

                return source.filter((option) => {
                    if (seen.has(option.value)) {
                        return false;
                    }

                    seen.add(option.value);

                    return true;
                });
            });
            pageRef.current = payload.meta.current_page;
            lastPageRef.current = payload.meta.last_page;
        } catch (error) {
            if ((error as Error).name === 'AbortError' || signal?.aborted) {
                return;
            }

            if (replace) {
                setOptions([]);
            }
        } finally {
            if (!signal?.aborted) {
                loadingRef.current = false;
                setLoading(false);
            }
        }
    };

    const handleScroll = (event: React.UIEvent<HTMLDivElement>) => {
        const target = event.currentTarget;

        if (
            loadingRef.current ||
            pageRef.current >= lastPageRef.current ||
            target.scrollTop + target.clientHeight < target.scrollHeight - 48
        ) {
            return;
        }

        void loadPage(
            pageRef.current + 1,
            debouncedSearch,
            false,
            controllerRef.current?.signal,
        );
    };

    const count = selectedItems.length;
    const displayLabel =
        count === 0
            ? placeholder
            : count === 1
              ? selectedItems[0]?.label
              : `${count} selected`;

    return (
        <div className="grid gap-2">
            <Popover
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);

                    if (next) {
                        setSearch('');
                        setDebouncedSearch('');
                        setOptions([]);
                        pageRef.current = 1;
                        lastPageRef.current = 1;
                    }
                }}
            >
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className={cn(
                            'h-9 w-full justify-between px-3 font-normal',
                            className,
                        )}
                    >
                        <span
                            className={cn(
                                'truncate',
                                count === 0 ? 'text-muted-foreground' : '',
                            )}
                        >
                            {displayLabel}
                        </span>
                        <ChevronsUpDown className="size-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-(--radix-popover-trigger-width) p-0"
                    align="start"
                >
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder={searchPlaceholder}
                            value={search}
                            onValueChange={setSearch}
                        />
                        <CommandList onScroll={handleScroll}>
                            <CommandGroup>
                                {options.map((option) => {
                                    const isSelected = selected.has(
                                        option.value,
                                    );

                                    return (
                                        <CommandItem
                                            key={option.value}
                                            value={`${option.label} ${option.value}`}
                                            onSelect={() =>
                                                onToggle(option, !isSelected)
                                            }
                                        >
                                            <span className="flex min-w-0 flex-1 flex-col">
                                                <span className="truncate">
                                                    {option.label}
                                                </span>
                                                {option.description ? (
                                                    <span className="text-muted-foreground truncate text-xs">
                                                        {option.description}
                                                    </span>
                                                ) : null}
                                            </span>
                                            <Check
                                                className={cn(
                                                    'ml-auto',
                                                    isSelected
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                        </CommandItem>
                                    );
                                })}
                                {options.length === 0 && !loading ? (
                                    <p className="text-muted-foreground py-4 text-center text-sm">
                                        {emptyText}
                                    </p>
                                ) : null}
                                {loading ? (
                                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-2 text-xs">
                                        <Loader2 className="size-3.5 animate-spin" />
                                        Loading…
                                    </div>
                                ) : null}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            {showSelectedBadges && selectedItems.length > 0 ? (
                <div className="flex flex-wrap gap-1.5">
                    {selectedItems.map((item) => (
                        <Badge
                            key={item.value}
                            variant="secondary"
                            className="max-w-full gap-1 pr-1 font-normal"
                        >
                            <span className="truncate">{item.label}</span>
                            <button
                                type="button"
                                className="hover:bg-muted rounded-sm p-0.5"
                                aria-label={`Remove ${item.label}`}
                                onClick={() => onToggle(item, false)}
                            >
                                <XIcon className="size-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            ) : null}
        </div>
    );
}
