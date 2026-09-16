import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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

type ComboboxOption = {
    value: string;
    label: string;
    description?: string | null;
};

export function InfiniteScrollCombobox({
    optionsUrl,
    value,
    onValueChange,
    selectedLabel,
    placeholder,
    searchPlaceholder,
    emptyText,
    noneLabel,
    className,
    descriptionKey = 'sku',
}: {
    optionsUrl: string;
    value: string | null;
    onValueChange: (value: string | null) => void;
    selectedLabel?: string | null;
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
    noneLabel: string;
    className?: string;
    descriptionKey?: 'sku' | null;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [options, setOptions] = useState<ComboboxOption[]>([]);
    const [loading, setLoading] = useState(false);
    const loadingRef = useRef(false);
    const pageRef = useRef(1);
    const lastPageRef = useRef(1);
    const controllerRef = useRef<AbortController | null>(null);

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

    const displayLabel =
        value && value !== 'all'
            ? (selectedLabel ??
              options.find((option) => option.value === value)?.label ??
              placeholder)
            : noneLabel;

    return (
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
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'h-9 justify-between px-3 font-normal',
                        className,
                    )}
                >
                    <span className="truncate">{displayLabel}</span>
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
                            <CommandItem
                                value="all"
                                onSelect={() => {
                                    onValueChange(null);
                                    setOpen(false);
                                }}
                            >
                                {noneLabel}
                                <Check
                                    className={cn(
                                        'ml-auto',
                                        !value || value === 'all'
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )}
                                />
                            </CommandItem>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={`${option.label} ${option.value}`}
                                    onSelect={() => {
                                        onValueChange(option.value);
                                        setOpen(false);
                                    }}
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
                                            value === option.value
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                            ))}
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
    );
}
