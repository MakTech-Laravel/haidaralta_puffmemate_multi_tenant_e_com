export type LucideIconPickerMode = 'collapsible' | 'dialog' | 'sheet';

export type LucideIconPickerTriggerVariant =
    | 'field'
    | 'compact'
    | 'button'
    | 'ghost';

export type LucideIconPickerDensity = 'comfortable' | 'compact';

export type LucideIconPickerPanelBehavior = 'inline' | 'overlay';

export type LucideIconPickerSize = 'sm' | 'md' | 'lg';

export type LucideIconPickerPanelAlign = 'start' | 'end' | 'auto';

/** Override chrome copy for i18n / product wording. */
export type IconPickerLabels = {
    search: string;
    recent: string;
    clearRecents: string;
    selected: string;
    preview: string;
    cancel: string;
    confirm: string;
    copyKey: string;
    copied: string;
    invalidValue: string;
    empty: string;
    loading: string;
};

/** Per-slot className overrides — pass only what you need. */
export interface LucideIconPickerClassNames {
    wrapper?: string;
    shell?: string;
    trigger?: string;
    triggerPreview?: string;
    triggerLabel?: string;
    triggerDescription?: string;
    triggerAction?: string;
    panel?: string;
    search?: string;
    searchInput?: string;
    status?: string;
    loading?: string;
    empty?: string;
    grid?: string;
    option?: string;
    optionSelected?: string;
    optionPending?: string;
    optionLabel?: string;
    recents?: string;
    category?: string;
    categoryActive?: string;
    preview?: string;
    confirmButton?: string;
    dialogContent?: string;
    dialogHeader?: string;
    sheetContent?: string;
    error?: string;
}

export interface LucideIconPickerHandle {
    open: () => void;
    close: () => void;
    clearSearch: () => void;
    focus: () => void;
}

/**
 * Lucide-only icon picker (shadcn + Motion).
 *
 * Recommended footprints:
 * - Form field → default collapsible (`triggerVariant="field"`)
 * - Toolbar / compact → `mode="dialog"` or omit mode with `triggerVariant="compact"`
 * - Product sets → pass `allowedIcons` to skip the full catalog load
 */
export type LucideIconPickerProps = {
    name?: string;
    id?: string;
    /** Alias for `classNames.wrapper`. */
    className?: string;
    classNames?: LucideIconPickerClassNames;
    defaultValue?: string | null;
    value?: string;
    onChange?: (icon: string) => void;
    /** Fires when pending (unconfirmed) icon changes in dialog/sheet. */
    onPendingChange?: (icon: string | null) => void;
    /**
     * Fires when an incoming value/default is not a valid Lucide key.
     * Display still resolves via `fallbackIcon`.
     */
    onInvalidValue?: (raw: string, resolved: string) => void;
    label?: string;
    description?: string;
    /** Fallback when resolving invalid keys. @default 'pen-line' */
    fallbackIcon?: string;
    defaultIcon?: string;
    defaultOpen?: boolean;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    disabled?: boolean;
    required?: boolean;
    error?: string;
    placeholder?: string;
    allowedIcons?: string[];
    /**
     * Limit category chips to this subset (intersection with available).
     */
    categories?: string[];
    /** Chrome string overrides. */
    labels?: Partial<IconPickerLabels>;
    /**
     * Dialog / sheet width footprint.
     * @default 'md'
     */
    size?: LucideIconPickerSize;
    /**
     * Collapsible overlay alignment. `auto` measures the viewport.
     * @default 'auto' for overlay
     */
    panelAlign?: LucideIconPickerPanelAlign;
    /**
     * localStorage namespace for recents.
     * @default 'global'
     */
    recentsScope?: string;
    showSparkles?: boolean;
    /**
     * Presentation shell.
     * When omitted: `compact`/`ghost` → dialog; otherwise collapsible.
     */
    mode?: LucideIconPickerMode;
    /**
     * Trigger footprint.
     * @default 'field'
     */
    triggerVariant?: LucideIconPickerTriggerVariant;
    /**
     * How the collapsible panel is positioned.
     * @default 'overlay' for compact/ghost, 'inline' for field
     */
    panelBehavior?: LucideIconPickerPanelBehavior;
    /**
     * Grid density.
     * @default 'comfortable'
     */
    density?: LucideIconPickerDensity;
    closeOnSelect?: boolean;
    clearSearchOnSelect?: boolean;
    /**
     * Require Confirm in dialog/sheet before committing.
     * @default true when mode is dialog or sheet; false for collapsible
     */
    confirmSelection?: boolean;
    /** Show recent icons strip. @default true */
    showRecents?: boolean;
    /** Show category filter chips. @default true */
    showCategories?: boolean;
    dialogTitle?: string;
    dialogDescription?: string;
};

export type CatalogIconOption = {
    key: string;
    label: string;
    searchText: string;
    categories: readonly string[];
};

export const DEFAULT_ICON_PICKER_LABELS: IconPickerLabels = {
    search: 'Search icons',
    recent: 'Recent',
    clearRecents: 'Clear',
    selected: 'Selected',
    preview: 'Preview',
    cancel: 'Cancel',
    confirm: 'Use icon',
    copyKey: 'Copy key',
    copied: 'Copied',
    invalidValue:
        'The previous icon was not recognized. Pick one from the list below.',
    empty: 'No icons match your filters.',
    loading: 'Loading icons…',
};
