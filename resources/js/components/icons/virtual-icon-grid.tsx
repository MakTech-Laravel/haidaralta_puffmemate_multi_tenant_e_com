import { Check } from 'lucide-react';
import {
    useCallback,
    useEffect,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { KeyboardEvent } from 'react';

import { CachedLucideIcon } from '@/components/icons/cached-lucide-icon';
import { IconPickerHoverTip } from '@/components/icons/icon-picker-hover-tip';
import type { LucideIconPickerDensity } from '@/components/icons/lucide-icon-picker-types';
import { prefetchLucideIcons } from '@/lib/lucide-icon-cache';
import { cn } from '@/lib/utils';

export type VirtualIconOption = {
    key: string;
    label: string;
};

type VirtualIconGridProps = {
    options: VirtualIconOption[];
    selected: string;
    pending?: string | null;
    onSelect: (key: string) => void;
    onEscape?: () => void;
    disabled?: boolean;
    id?: string;
    density?: LucideIconPickerDensity;
    className?: string;
    optionClassName?: string;
    optionSelectedClassName?: string;
    optionPendingClassName?: string;
    optionLabelClassName?: string;
};

type HoverTipState = {
    key: string;
    label: string;
    x: number;
    y: number;
};

const OVERSCAN_ROWS = 2;
const MIN_COLUMNS = 4;
const MAX_COLUMNS = 10;
const TIP_DELAY_MS = 280;

function densityConfig(density: LucideIconPickerDensity) {
    if (density === 'compact') {
        return {
            rowHeight: 52,
            rowGap: 6,
            minColumnWidth: 52,
            showLabel: false,
        };
    }

    return {
        rowHeight: 76,
        rowGap: 10,
        minColumnWidth: 72,
        showLabel: true,
    };
}

function columnCountForWidth(width: number, minColumnWidth: number): number {
    if (width <= 0) {
        return MIN_COLUMNS;
    }

    return Math.max(
        MIN_COLUMNS,
        Math.min(MAX_COLUMNS, Math.floor(width / minColumnWidth)),
    );
}

export function VirtualIconGrid({
    options,
    selected,
    pending = null,
    onSelect,
    onEscape,
    disabled = false,
    id,
    density = 'comfortable',
    className,
    optionClassName,
    optionSelectedClassName,
    optionPendingClassName,
    optionLabelClassName,
}: VirtualIconGridProps) {
    const { rowHeight, rowGap, minColumnWidth, showLabel } =
        densityConfig(density);
    const rowStride = rowHeight + rowGap;

    const scrollRef = useRef<HTMLDivElement>(null);
    const scrollRafRef = useRef<number | null>(null);
    const scrollTopRef = useRef(0);
    const tipDelayRef = useRef<number | null>(null);
    const [scrollTop, setScrollTop] = useState(0);
    const [viewportHeight, setViewportHeight] = useState(384);
    const [columnCount, setColumnCount] = useState(MIN_COLUMNS);
    const [focusedIndex, setFocusedIndex] = useState(() => {
        const active = pending ?? selected;
        const selectedIndex = options.findIndex(
            (option) => option.key === active,
        );

        return selectedIndex >= 0 ? selectedIndex : 0;
    });
    const [hoverTip, setHoverTip] = useState<HoverTipState | null>(null);

    const clearHoverTip = useCallback(() => {
        if (tipDelayRef.current != null) {
            window.clearTimeout(tipDelayRef.current);
            tipDelayRef.current = null;
        }

        setHoverTip(null);
    }, []);

    const scheduleHoverTip = useCallback(
        (option: VirtualIconOption, target: HTMLElement) => {
            if (tipDelayRef.current != null) {
                window.clearTimeout(tipDelayRef.current);
            }

            tipDelayRef.current = window.setTimeout(() => {
                const rect = target.getBoundingClientRect();
                setHoverTip({
                    key: option.key,
                    label: option.label,
                    x: rect.left + rect.width / 2,
                    y: rect.top,
                });
                tipDelayRef.current = null;
            }, TIP_DELAY_MS);
        },
        [],
    );

    useLayoutEffect(() => {
        const element = scrollRef.current;

        if (!element) {
            return;
        }

        const updateMetrics = () => {
            setViewportHeight(element.clientHeight);
            setColumnCount(
                columnCountForWidth(element.clientWidth - 24, minColumnWidth),
            );
        };

        updateMetrics();

        const observer = new ResizeObserver(updateMetrics);
        observer.observe(element);

        return () => observer.disconnect();
    }, [minColumnWidth]);

    const rowCount = Math.ceil(options.length / columnCount);
    const totalHeight = Math.max(0, rowCount * rowStride - rowGap);

    const { startRow, endRow } = useMemo(() => {
        const firstVisibleRow = Math.floor(scrollTop / rowStride);
        const visibleRowCount = Math.ceil(viewportHeight / rowStride);

        return {
            startRow: Math.max(0, firstVisibleRow - OVERSCAN_ROWS),
            endRow: Math.min(
                rowCount,
                firstVisibleRow + visibleRowCount + OVERSCAN_ROWS,
            ),
        };
    }, [rowCount, rowStride, scrollTop, viewportHeight]);

    const visibleRows = useMemo(() => {
        return Array.from({ length: endRow - startRow }, (_, offset) => {
            const row = startRow + offset;
            const startIndex = row * columnCount;

            return {
                row,
                startIndex,
                items: options.slice(startIndex, startIndex + columnCount),
            };
        });
    }, [columnCount, endRow, options, startRow]);

    useEffect(() => {
        prefetchLucideIcons(
            visibleRows.flatMap(({ items }) => items.map((item) => item.key)),
        );
    }, [visibleRows]);

    const ensureIndexVisible = useCallback(
        (index: number) => {
            const element = scrollRef.current;

            if (!element || options.length === 0) {
                return;
            }

            const row = Math.floor(index / columnCount);
            const rowTop = row * rowStride;
            const rowBottom = rowTop + rowHeight;
            const viewTop = element.scrollTop;
            const viewBottom = viewTop + element.clientHeight;

            if (rowTop < viewTop) {
                element.scrollTop = rowTop;
            } else if (rowBottom > viewBottom) {
                element.scrollTop = rowBottom - element.clientHeight;
            }
        },
        [columnCount, options.length, rowHeight, rowStride],
    );

    const moveFocus = useCallback(
        (nextIndex: number) => {
            if (options.length === 0) {
                return;
            }

            const clamped = Math.max(
                0,
                Math.min(options.length - 1, nextIndex),
            );
            setFocusedIndex(clamped);
            ensureIndexVisible(clamped);
        },
        [ensureIndexVisible, options.length],
    );

    const handleScroll = useCallback(() => {
        const element = scrollRef.current;

        if (!element) {
            return;
        }

        clearHoverTip();
        scrollTopRef.current = element.scrollTop;

        if (scrollRafRef.current !== null) {
            return;
        }

        scrollRafRef.current = requestAnimationFrame(() => {
            scrollRafRef.current = null;

            const nextScrollTop = scrollTopRef.current;
            const previousStart = Math.max(
                0,
                Math.floor(scrollTop / rowStride) - OVERSCAN_ROWS,
            );
            const nextStart = Math.max(
                0,
                Math.floor(nextScrollTop / rowStride) - OVERSCAN_ROWS,
            );

            if (previousStart !== nextStart || nextScrollTop !== scrollTop) {
                setScrollTop(nextScrollTop);
            }
        });
    }, [clearHoverTip, rowStride, scrollTop]);

    useEffect(() => {
        return () => {
            if (scrollRafRef.current !== null) {
                cancelAnimationFrame(scrollRafRef.current);
            }

            if (tipDelayRef.current != null) {
                window.clearTimeout(tipDelayRef.current);
            }
        };
    }, []);

    const handleKeyDown = useCallback(
        (event: KeyboardEvent<HTMLDivElement>) => {
            if (disabled || options.length === 0) {
                return;
            }

            const current = Math.max(
                0,
                Math.min(focusedIndex, options.length - 1),
            );

            switch (event.key) {
                case 'ArrowRight':
                    event.preventDefault();
                    moveFocus(current + 1);
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    moveFocus(current - 1);
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    moveFocus(current + columnCount);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    moveFocus(current - columnCount);
                    break;
                case 'Home':
                    event.preventDefault();
                    moveFocus(0);
                    break;
                case 'End':
                    event.preventDefault();
                    moveFocus(options.length - 1);
                    break;
                case 'Enter':
                case ' ': {
                    event.preventDefault();
                    const option = options[current];

                    if (option) {
                        onSelect(option.key);
                    }

                    break;
                }
                case 'Escape':
                    event.preventDefault();
                    clearHoverTip();
                    onEscape?.();
                    break;
                default:
                    break;
            }
        },
        [
            clearHoverTip,
            columnCount,
            disabled,
            focusedIndex,
            moveFocus,
            onEscape,
            onSelect,
            options,
        ],
    );

    const safeFocusedIndex =
        options.length === 0
            ? 0
            : Math.max(0, Math.min(focusedIndex, options.length - 1));

    return (
        <>
            <div
                ref={scrollRef}
                id={id}
                onScroll={handleScroll}
                onKeyDown={handleKeyDown}
                tabIndex={disabled ? -1 : 0}
                className={cn(
                    'max-h-80 scrollbar-thin overflow-y-auto rounded-xl border border-border/60 bg-muted/10 p-3 outline-none focus-visible:ring-2 focus-visible:ring-ring',
                    className,
                )}
                role="listbox"
                aria-label="Icons"
                aria-activedescendant={
                    options[safeFocusedIndex]
                        ? `${id ?? 'icon-grid'}-option-${options[safeFocusedIndex].key}`
                        : undefined
                }
                aria-disabled={disabled || undefined}
            >
                <div className="relative" style={{ height: totalHeight }}>
                    {visibleRows.map(({ row, startIndex, items }) => (
                        <div
                            key={row}
                            className="absolute inset-x-0 grid gap-2"
                            style={{
                                top: row * rowStride,
                                height: rowHeight,
                                gridTemplateColumns: `repeat(${columnCount}, minmax(0, 1fr))`,
                            }}
                        >
                            {items.map((option, offset) => {
                                const index = startIndex + offset;
                                const isSelected = selected === option.key;
                                const isPending = pending === option.key;
                                const isFocused = index === safeFocusedIndex;

                                return (
                                    <button
                                        key={option.key}
                                        id={`${id ?? 'icon-grid'}-option-${option.key}`}
                                        type="button"
                                        role="option"
                                        aria-selected={isSelected || isPending}
                                        aria-label={option.label}
                                        tabIndex={-1}
                                        disabled={disabled}
                                        onClick={() => onSelect(option.key)}
                                        onMouseEnter={(event) => {
                                            setFocusedIndex(index);
                                            scheduleHoverTip(
                                                option,
                                                event.currentTarget,
                                            );
                                        }}
                                        onMouseLeave={clearHoverTip}
                                        onFocus={(event) => {
                                            setFocusedIndex(index);
                                            scheduleHoverTip(
                                                option,
                                                event.currentTarget,
                                            );
                                        }}
                                        onBlur={clearHoverTip}
                                        className={cn(
                                            'relative flex w-full flex-col items-center justify-center gap-1.5 rounded-lg border border-transparent bg-background px-1.5 py-1 text-center transition-colors hover:bg-muted/40',
                                            isSelected &&
                                                cn(
                                                    'border-primary bg-primary/15 text-primary shadow-xs',
                                                    optionSelectedClassName,
                                                ),
                                            isPending &&
                                                !isSelected &&
                                                cn(
                                                    'border-2 border-dashed border-primary bg-primary/10 text-primary',
                                                    optionPendingClassName,
                                                ),
                                            isFocused &&
                                                !isSelected &&
                                                !isPending &&
                                                'bg-muted/60 ring-1 ring-border',
                                            optionClassName,
                                        )}
                                    >
                                        {isSelected ? (
                                            <Check
                                                className="absolute top-1 right-1 size-3 text-primary"
                                                aria-hidden
                                            />
                                        ) : null}
                                        {isPending && !isSelected ? (
                                            <span className="absolute top-1 right-1 size-1.5 rounded-full bg-primary" />
                                        ) : null}
                                        <CachedLucideIcon
                                            name={option.key}
                                            className="size-5 shrink-0"
                                        />
                                        {showLabel ? (
                                            <span
                                                className={cn(
                                                    'line-clamp-1 w-full px-0.5 text-[10px] leading-tight font-medium text-muted-foreground',
                                                    optionLabelClassName,
                                                )}
                                            >
                                                {option.label}
                                            </span>
                                        ) : (
                                            <span className="sr-only">
                                                {option.label}
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>

            <IconPickerHoverTip
                open={hoverTip != null}
                label={hoverTip?.label ?? ''}
                iconKey={hoverTip?.key ?? ''}
                x={hoverTip?.x ?? 0}
                y={hoverTip?.y ?? 0}
            />
        </>
    );
}
