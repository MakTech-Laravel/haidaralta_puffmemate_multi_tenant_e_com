import type { CatalogIconOption } from '@/components/icons/lucide-icon-picker-types';
import { getIconLabel, isValidLucideIconKey } from '@/lib/icons';

type IconCatalogModule = {
    ICON_OPTIONS: readonly CatalogIconOption[];
    ICON_CATEGORIES: readonly string[];
};

type LoadedCatalog = {
    options: readonly CatalogIconOption[];
    categories: readonly string[];
};

let catalogPromise: Promise<LoadedCatalog> | null = null;
let catalogCache: LoadedCatalog | null = null;

export function loadIconCatalog(): Promise<LoadedCatalog> {
    if (catalogCache) {
        return Promise.resolve(catalogCache);
    }

    if (catalogPromise) {
        return catalogPromise;
    }

    catalogPromise = import('@/lib/icon-catalog')
        .then((module: IconCatalogModule) => {
            const loaded: LoadedCatalog = {
                options: module.ICON_OPTIONS,
                categories: module.ICON_CATEGORIES,
            };
            catalogCache = loaded;

            return loaded;
        })
        .catch(() => {
            catalogPromise = null;

            return {
                options: [] as CatalogIconOption[],
                categories: [] as string[],
            };
        });

    return catalogPromise;
}

/** Idle / route-level prefetch helper. */
export function prefetchIconCatalog(): void {
    void loadIconCatalog();
}

/**
 * Build a lightweight catalog from an allow-list without loading the full
 * ~330KB icon-catalog module.
 */
export function buildAllowListOptions(
    allowedIcons: string[],
): CatalogIconOption[] {
    const seen = new Set<string>();
    const options: CatalogIconOption[] = [];

    for (const raw of allowedIcons) {
        if (!raw || seen.has(raw) || !isValidLucideIconKey(raw)) {
            continue;
        }

        seen.add(raw);
        const label = getIconLabel(raw);
        options.push({
            key: raw,
            label,
            searchText: `${raw} ${label}`.toLowerCase(),
            categories: [],
        });
    }

    return options;
}
