import {
    useCallback,
    useDeferredValue,
    useEffect,
    useId,
    useMemo,
    useRef,
    useState,
} from 'react';

import {
    buildAllowListOptions,
    loadIconCatalog,
} from '@/components/icons/icon-catalog-loader';
import {
    clearRecentIcons,
    pushRecentIcon,
    readRecentIcons,
} from '@/components/icons/icon-picker-recents';
import type {
    CatalogIconOption,
    IconPickerLabels,
    LucideIconPickerMode,
    LucideIconPickerProps,
} from '@/components/icons/lucide-icon-picker-types';
import { DEFAULT_ICON_PICKER_LABELS } from '@/components/icons/lucide-icon-picker-types';
import {
    getIconLabel,
    isValidLucideIconKey,
    resolveIconKey,
} from '@/lib/icons';
import { prefetchLucideIcons } from '@/lib/lucide-icon-cache';

type UseIconPickerStateOptions = Pick<
    LucideIconPickerProps,
    | 'id'
    | 'defaultValue'
    | 'value'
    | 'onChange'
    | 'onPendingChange'
    | 'onInvalidValue'
    | 'fallbackIcon'
    | 'defaultIcon'
    | 'defaultOpen'
    | 'open'
    | 'onOpenChange'
    | 'disabled'
    | 'placeholder'
    | 'allowedIcons'
    | 'categories'
    | 'labels'
    | 'recentsScope'
    | 'closeOnSelect'
    | 'clearSearchOnSelect'
    | 'confirmSelection'
    | 'showRecents'
    | 'mode'
> & {
    mode: LucideIconPickerMode;
};

function mergeLabels(overrides?: Partial<IconPickerLabels>): IconPickerLabels {
    return { ...DEFAULT_ICON_PICKER_LABELS, ...overrides };
}

/** Stable empty reference so derived memos don't invalidate every render. */
const NO_CATEGORIES: readonly string[] = [];

export function useIconPickerState({
    id,
    defaultValue,
    value,
    onChange,
    onPendingChange,
    onInvalidValue,
    fallbackIcon = 'pen-line',
    defaultIcon = 'pen-line',
    defaultOpen = false,
    open: openProp,
    onOpenChange,
    disabled = false,
    placeholder,
    allowedIcons,
    categories: categoriesProp,
    labels: labelsProp,
    recentsScope = 'global',
    closeOnSelect = false,
    clearSearchOnSelect = false,
    confirmSelection: confirmSelectionProp,
    showRecents = true,
    mode,
}: UseIconPickerStateOptions) {
    const reactId = useId();
    const searchId = id ? `${id}-search` : `${reactId}-search`;
    const gridId = id ? `${id}-grid` : `${reactId}-grid`;
    const statusId = id ? `${id}-status` : `${reactId}-status`;
    const triggerRef = useRef<HTMLButtonElement>(null);
    const invalidNotifiedRef = useRef<string | null>(null);

    const labels = useMemo(() => mergeLabels(labelsProp), [labelsProp]);

    const confirmSelection =
        confirmSelectionProp ?? (mode === 'dialog' || mode === 'sheet');

    const isControlledValue = value !== undefined;
    const isControlledOpen = openProp !== undefined;
    const useAllowListOnly = Boolean(allowedIcons && allowedIcons.length > 0);

    const rawIncoming = isControlledValue
        ? value
        : (defaultValue ?? defaultIcon);

    const [selected, setSelected] = useState(() =>
        resolveIconKey(rawIncoming, fallbackIcon),
    );
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query);
    const [category, setCategory] = useState<string | null>(null);
    const [uncontrolledOpen, setUncontrolledOpen] = useState(defaultOpen);
    const [loadedCatalog, setLoadedCatalog] = useState<
        readonly CatalogIconOption[] | null
    >(null);
    const [loadedCategories, setLoadedCategories] =
        useState<readonly string[]>(NO_CATEGORIES);
    const [recents, setRecents] = useState<string[]>([]);
    const [pendingIcon, setPendingIcon] = useState<string | null>(null);

    const open = isControlledOpen ? openProp : uncontrolledOpen;

    const displayIcon = isControlledValue
        ? resolveIconKey(value, fallbackIcon)
        : selected;

    const hadInvalidValue = useMemo(() => {
        const raw = isControlledValue ? value : defaultValue;

        return raw != null && raw !== '' && !isValidLucideIconKey(raw);
    }, [defaultValue, isControlledValue, value]);

    useEffect(() => {
        if (!hadInvalidValue) {
            return;
        }

        const raw = (isControlledValue ? value : defaultValue) ?? '';

        if (invalidNotifiedRef.current === raw) {
            return;
        }

        invalidNotifiedRef.current = raw;
        onInvalidValue?.(raw, resolveIconKey(raw, fallbackIcon));
    }, [
        defaultValue,
        fallbackIcon,
        hadInvalidValue,
        isControlledValue,
        onInvalidValue,
        value,
    ]);

    const setOpen = useCallback(
        (nextOpen: boolean) => {
            if (disabled) {
                return;
            }

            if (!isControlledOpen) {
                setUncontrolledOpen(nextOpen);
            }

            onOpenChange?.(nextOpen);

            if (!nextOpen) {
                setQuery('');
                setCategory(null);
                setPendingIcon(null);
                onPendingChange?.(null);
            } else {
                if (showRecents) {
                    setRecents(readRecentIcons(recentsScope));
                }

                if (confirmSelection) {
                    setPendingIcon(displayIcon);
                    onPendingChange?.(displayIcon);
                }
            }
        },
        [
            confirmSelection,
            disabled,
            displayIcon,
            isControlledOpen,
            onOpenChange,
            onPendingChange,
            recentsScope,
            showRecents,
        ],
    );

    // An allow list is derived straight from props; only the full catalog is
    // fetched, so it is the only part that needs to live in state.
    const allowListCatalog = useMemo(
        () =>
            useAllowListOnly ? buildAllowListOptions(allowedIcons ?? []) : null,
        [allowedIcons, useAllowListOnly],
    );

    const catalog = allowListCatalog ?? loadedCatalog;
    const categories = useAllowListOnly ? NO_CATEGORIES : loadedCategories;

    const catalogLoading = open && catalog === null && !useAllowListOnly;

    useEffect(() => {
        void prefetchLucideIcons([displayIcon]);
    }, [displayIcon]);

    useEffect(() => {
        if (useAllowListOnly || !open || loadedCatalog) {
            return;
        }

        let cancelled = false;

        void loadIconCatalog().then((loaded) => {
            if (!cancelled) {
                setLoadedCatalog(loaded.options);
                setLoadedCategories(loaded.categories);
            }
        });

        return () => {
            cancelled = true;
        };
    }, [loadedCatalog, open, useAllowListOnly]);

    const baseOptions = useMemo(() => {
        if (!catalog) {
            return [] as CatalogIconOption[];
        }

        if (useAllowListOnly) {
            return catalog as CatalogIconOption[];
        }

        return catalog as CatalogIconOption[];
    }, [catalog, useAllowListOnly]);

    const categoryOptions = useMemo(() => {
        if (!category) {
            return baseOptions;
        }

        return baseOptions.filter((option) =>
            option.categories.includes(category),
        );
    }, [baseOptions, category]);

    const filteredOptions = useMemo(() => {
        const normalizedQuery = deferredQuery.trim().toLowerCase();

        if (normalizedQuery === '') {
            return categoryOptions;
        }

        return categoryOptions.filter((option) =>
            option.searchText.includes(normalizedQuery),
        );
    }, [categoryOptions, deferredQuery]);

    const availableCategories = useMemo(() => {
        if (useAllowListOnly || categories.length === 0) {
            return [] as string[];
        }

        const present = new Set<string>();

        for (const option of baseOptions) {
            for (const item of option.categories) {
                present.add(item);
            }
        }

        const fromCatalog = categories.filter((item) => present.has(item));

        if (!categoriesProp || categoriesProp.length === 0) {
            return fromCatalog;
        }

        const allow = new Set(categoriesProp);

        return fromCatalog.filter((item) => allow.has(item));
    }, [baseOptions, categories, categoriesProp, useAllowListOnly]);

    const recentOptions = useMemo(() => {
        if (!showRecents || recents.length === 0) {
            return [] as CatalogIconOption[];
        }

        const byKey = new Map(
            baseOptions.map((option) => [option.key, option]),
        );

        return recents
            .map((key) => byKey.get(key))
            .filter((option): option is CatalogIconOption => Boolean(option));
    }, [baseOptions, recents, showRecents]);

    const isSearchPending = query !== deferredQuery;

    const activeIcon = confirmSelection
        ? (pendingIcon ?? displayIcon)
        : displayIcon;

    const displayLabel = useMemo(() => {
        const fromCatalog = catalog?.find(
            (option) => option.key === displayIcon,
        )?.label;

        return fromCatalog ?? getIconLabel(displayIcon);
    }, [catalog, displayIcon]);

    const activeLabel = useMemo(() => {
        const fromCatalog = catalog?.find(
            (option) => option.key === activeIcon,
        )?.label;

        return fromCatalog ?? getIconLabel(activeIcon);
    }, [activeIcon, catalog]);

    const commitIcon = useCallback(
        (key: string, options?: { close?: boolean }) => {
            if (disabled) {
                return;
            }

            if (!isControlledValue) {
                setSelected(key);
            }

            onChange?.(key);

            if (showRecents) {
                setRecents(pushRecentIcon(key, recentsScope));
            }

            if (clearSearchOnSelect) {
                setQuery('');
            }

            const shouldClose = options?.close ?? closeOnSelect;

            if (shouldClose) {
                setOpen(false);
                triggerRef.current?.focus();
            }
        },
        [
            clearSearchOnSelect,
            closeOnSelect,
            disabled,
            isControlledValue,
            onChange,
            recentsScope,
            setOpen,
            showRecents,
        ],
    );

    const selectIcon = useCallback(
        (key: string) => {
            if (disabled) {
                return;
            }

            if (confirmSelection) {
                setPendingIcon(key);
                onPendingChange?.(key);

                return;
            }

            commitIcon(key);
        },
        [commitIcon, confirmSelection, disabled, onPendingChange],
    );

    const confirmPending = useCallback(() => {
        const key = pendingIcon ?? displayIcon;
        commitIcon(key, { close: true });
        onPendingChange?.(null);
    }, [commitIcon, displayIcon, onPendingChange, pendingIcon]);

    const clearRecents = useCallback(() => {
        setRecents(clearRecentIcons(recentsScope));
    }, [recentsScope]);

    const statusMessage = catalogLoading
        ? labels.loading
        : filteredOptions.length === 0
          ? labels.empty
          : `${filteredOptions.length.toLocaleString()} icons`;

    const searchPlaceholder =
        placeholder ??
        (catalog
            ? `Search ${baseOptions.length.toLocaleString()} icons…`
            : labels.search);

    const clearSearch = useCallback(() => {
        setQuery('');
    }, []);

    const focus = useCallback(() => {
        triggerRef.current?.focus();
    }, []);

    const focusSearch = useCallback(() => {
        document.getElementById(searchId)?.focus();
    }, [searchId]);

    return {
        searchId,
        gridId,
        statusId,
        triggerRef,
        open,
        setOpen,
        query,
        setQuery,
        deferredQuery,
        category,
        setCategory,
        catalogLoading,
        filteredOptions,
        availableCategories,
        recentOptions,
        isSearchPending,
        displayIcon,
        displayLabel,
        activeIcon,
        activeLabel,
        pendingIcon,
        confirmSelection,
        selectIcon,
        confirmPending,
        clearRecents,
        hadInvalidDefault: hadInvalidValue,
        statusMessage,
        searchPlaceholder,
        clearSearch,
        focus,
        focusSearch,
        labels,
    };
}

export type IconPickerState = ReturnType<typeof useIconPickerState>;
