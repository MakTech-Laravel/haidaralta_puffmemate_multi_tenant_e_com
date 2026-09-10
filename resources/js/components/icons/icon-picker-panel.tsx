import { Search, X } from 'lucide-react';

import { CachedLucideIcon } from '@/components/icons/cached-lucide-icon';
import { IconPickerCategoryRail } from '@/components/icons/icon-picker-category-rail';
import type {
    CatalogIconOption,
    IconPickerLabels,
    LucideIconPickerClassNames,
    LucideIconPickerDensity,
} from '@/components/icons/lucide-icon-picker-types';
import { VirtualIconGrid } from '@/components/icons/virtual-icon-grid';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type IconPickerPanelProps = {
    searchId: string;
    gridId: string;
    statusId: string;
    label: string;
    query: string;
    onQueryChange: (query: string) => void;
    onEscape: () => void;
    searchPlaceholder: string;
    disabled?: boolean;
    catalogLoading: boolean;
    statusMessage: string;
    error?: string;
    isSearchPending: boolean;
    hadInvalidDefault: boolean;
    filteredOptions: CatalogIconOption[];
    deferredQuery: string;
    selectedIcon: string;
    pendingIcon?: string | null;
    onSelect: (key: string) => void;
    categories?: readonly string[];
    activeCategory?: string | null;
    onCategoryChange?: (category: string | null) => void;
    recentOptions?: CatalogIconOption[];
    onClearRecents?: () => void;
    showCategories?: boolean;
    showRecents?: boolean;
    density?: LucideIconPickerDensity;
    fillHeight?: boolean;
    labels: IconPickerLabels;
    classNames?: LucideIconPickerClassNames;
};

export function IconPickerPanel({
    searchId,
    gridId,
    statusId,
    label,
    query,
    onQueryChange,
    onEscape,
    searchPlaceholder,
    disabled = false,
    catalogLoading,
    statusMessage,
    error,
    isSearchPending,
    hadInvalidDefault,
    filteredOptions,
    deferredQuery,
    selectedIcon,
    pendingIcon = null,
    onSelect,
    categories = [],
    activeCategory = null,
    onCategoryChange,
    recentOptions = [],
    onClearRecents,
    showCategories = true,
    showRecents = true,
    density = 'comfortable',
    fillHeight = false,
    labels,
    classNames,
}: IconPickerPanelProps) {
    return (
        <div
            className={cn(
                'flex min-h-0 flex-col gap-4 px-5 py-5',
                fillHeight && 'h-full',
                classNames?.panel,
            )}
        >
            {hadInvalidDefault ? (
                <p className="shrink-0 text-xs text-amber-700 dark:text-amber-400">
                    {labels.invalidValue}
                </p>
            ) : null}

            <div
                className={cn(
                    'grid shrink-0 gap-1.5',
                    fillHeight
                        ? 'bg-background'
                        : 'bg-background/95 sticky top-0 z-10 pb-1 backdrop-blur-sm',
                    classNames?.search,
                )}
            >
                <label
                    htmlFor={searchId}
                    className="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                >
                    {label || labels.search}
                </label>
                <div className="relative">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        id={searchId}
                        value={query}
                        onChange={(event) => onQueryChange(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Escape') {
                                event.preventDefault();
                                onEscape();
                            }
                        }}
                        placeholder={searchPlaceholder}
                        className={cn(
                            'bg-background pr-9 pl-9',
                            classNames?.searchInput,
                        )}
                        autoComplete="off"
                        disabled={disabled || catalogLoading}
                        aria-controls={gridId}
                        aria-describedby={statusId}
                    />
                    {query ? (
                        <button
                            type="button"
                            className="text-muted-foreground hover:bg-muted hover:text-foreground absolute top-1/2 right-2.5 -translate-y-1/2 rounded-md p-0.5"
                            onClick={() => onQueryChange('')}
                            aria-label="Clear search"
                        >
                            <X className="size-3.5" />
                        </button>
                    ) : null}
                </div>
            </div>

            {showRecents && recentOptions.length > 0 ? (
                <div className={cn('shrink-0 space-y-2', classNames?.recents)}>
                    <div className="flex items-center justify-between gap-2">
                        <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                            {labels.recent}
                        </p>
                        {onClearRecents ? (
                            <button
                                type="button"
                                disabled={disabled}
                                onClick={onClearRecents}
                                className="text-muted-foreground hover:text-foreground text-[11px] font-medium transition-colors"
                            >
                                {labels.clearRecents}
                            </button>
                        ) : null}
                    </div>
                    <div className="flex scrollbar-none gap-2 overflow-x-auto">
                        {recentOptions.map((option) => (
                            <button
                                key={option.key}
                                type="button"
                                title={option.label}
                                disabled={disabled}
                                onClick={() => onSelect(option.key)}
                                className={cn(
                                    'border-border/70 bg-background hover:bg-muted/50 flex size-9 shrink-0 items-center justify-center rounded-lg border transition-colors',
                                    (pendingIcon ?? selectedIcon) ===
                                        option.key &&
                                        'border-primary bg-primary/10 text-primary',
                                )}
                            >
                                <CachedLucideIcon
                                    name={option.key}
                                    className="size-4"
                                />
                            </button>
                        ))}
                    </div>
                </div>
            ) : null}

            {showCategories && categories.length > 0 ? (
                <IconPickerCategoryRail
                    categories={categories}
                    activeCategory={activeCategory}
                    onCategoryChange={onCategoryChange}
                    disabled={disabled}
                    className={cn('shrink-0', classNames?.category)}
                    activeClassName={classNames?.categoryActive}
                />
            ) : null}

            <div className="flex shrink-0 items-center justify-between gap-2">
                <p
                    id={statusId}
                    className={cn(
                        'text-muted-foreground text-xs',
                        classNames?.status,
                    )}
                    aria-live="polite"
                >
                    {error ? error : statusMessage}
                    {isSearchPending ? ' Updating…' : ''}
                </p>
            </div>

            {catalogLoading ? (
                <div
                    className={cn(
                        'border-border/70 grid grid-cols-6 gap-2 rounded-xl border border-dashed p-3',
                        fillHeight && 'min-h-0 flex-1',
                        classNames?.loading,
                    )}
                >
                    {Array.from({ length: 12 }).map((_, index) => (
                        <div
                            key={index}
                            className="bg-muted/60 aspect-square animate-pulse rounded-lg"
                        />
                    ))}
                </div>
            ) : filteredOptions.length === 0 ? (
                <p
                    className={cn(
                        'border-border/70 text-muted-foreground rounded-xl border border-dashed px-4 py-10 text-center text-sm',
                        fillHeight && 'min-h-0 flex-1',
                        classNames?.empty,
                    )}
                >
                    {labels.empty}
                </p>
            ) : (
                <div
                    className={cn(
                        'min-h-0',
                        fillHeight && 'flex-1',
                        isSearchPending && 'opacity-70',
                    )}
                >
                    <VirtualIconGrid
                        key={`${deferredQuery.trim().toLowerCase()}-${activeCategory ?? 'all'}-${density}`}
                        id={gridId}
                        options={filteredOptions}
                        selected={selectedIcon}
                        pending={pendingIcon}
                        onSelect={onSelect}
                        onEscape={onEscape}
                        disabled={disabled}
                        density={density}
                        className={cn(
                            fillHeight && 'h-full max-h-none',
                            classNames?.grid,
                        )}
                        optionClassName={classNames?.option}
                        optionSelectedClassName={classNames?.optionSelected}
                        optionPendingClassName={classNames?.optionPending}
                        optionLabelClassName={classNames?.optionLabel}
                    />
                </div>
            )}
        </div>
    );
}
