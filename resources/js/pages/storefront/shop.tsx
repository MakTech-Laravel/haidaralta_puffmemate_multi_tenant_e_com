import { Head, router } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import {
    ShopFiltersSidebar,
    type ShopCategoryOption,
    type ShopPriceRangeOption,
    type ShopSidebarFilters,
    type ShopTagOption,
} from '@/components/storefront/shop-filters-sidebar';
import { StorefrontSearchBar } from '@/components/storefront/storefront-search-bar';
import { StorefrontSortSelect } from '@/components/storefront/storefront-sort-select';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { storefrontFeatures } from '@/lib/storefront-features';
import { shop } from '@/routes';
import type { StorefrontProduct } from '@/types/storefront';

/** Matches Tailwind `lg` (sidebar column vs Sheet). */
const LG_BREAKPOINT = 1024;

const lgMql =
    typeof window === 'undefined'
        ? undefined
        : window.matchMedia(`(max-width: ${LG_BREAKPOINT - 1}px)`);

function subscribeBelowLg(callback: (event: MediaQueryListEvent) => void) {
    if (!lgMql) {
        return () => {};
    }

    lgMql.addEventListener('change', callback);

    return () => {
        lgMql.removeEventListener('change', callback);
    };
}

function useIsBelowLg(): boolean {
    return useSyncExternalStore(
        subscribeBelowLg,
        () => lgMql?.matches ?? false,
        () => false,
    );
}

type ShopFilters = {
    search: string;
    sort: string | null;
    minPrice: number | null;
    maxPrice: number | null;
    categories: string[];
    tags: string[];
};

type ShopFilterOptions = {
    categories: ShopCategoryOption[];
    tags: ShopTagOption[];
    priceRanges: ShopPriceRangeOption[];
    sliderMin: number;
    sliderMax: number;
};

type ShopPageProps = {
    products: StorefrontProduct[];
    filters: ShopFilters;
    filterOptions: ShopFilterOptions;
};

type VisitOptions = Partial<ShopFilters> & {
    search?: string;
};

function toSidebarFilters(filters: ShopFilters): ShopSidebarFilters {
    return {
        minPrice: filters.minPrice,
        maxPrice: filters.maxPrice,
        categories: filters.categories,
        tags: filters.tags,
    };
}

export default function StorefrontShop({
    products,
    filters,
    filterOptions,
}: ShopPageProps) {
    const isBelowLg = useIsBelowLg();
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const [sidebar, setSidebar] = useState<ShopSidebarFilters>(() =>
        toSidebarFilters(filters),
    );
    const [sort, setSort] = useState(filters.sort);
    const [updating, setUpdating] = useState(false);
    const visitId = useRef(0);

    useEffect(() => {
        setSearch(filters.search);
        setSidebar(toSidebarFilters(filters));
        setSort(filters.sort);
    }, [filters]);

    const visit = (next: VisitOptions = {}) => {
        const nextSearch = next.search ?? search;
        const nextSort = next.sort !== undefined ? next.sort : sort;
        const nextMinPrice =
            next.minPrice !== undefined ? next.minPrice : sidebar.minPrice;
        const nextMaxPrice =
            next.maxPrice !== undefined ? next.maxPrice : sidebar.maxPrice;
        const nextCategories =
            next.categories !== undefined
                ? next.categories
                : sidebar.categories;
        const nextTags = next.tags !== undefined ? next.tags : sidebar.tags;

        const id = visitId.current + 1;
        visitId.current = id;
        setUpdating(true);
        router.cancelAll();
        router.get(
            shop.url(),
            {
                ...(nextSearch ? { search: nextSearch } : {}),
                ...(nextSort ? { sort: nextSort } : {}),
                ...(nextMinPrice !== null && nextMinPrice !== undefined
                    ? { min_price: nextMinPrice }
                    : {}),
                ...(nextMaxPrice !== null && nextMaxPrice !== undefined
                    ? { max_price: nextMaxPrice }
                    : {}),
                ...(nextCategories.length > 0
                    ? { categories: nextCategories }
                    : {}),
                ...(nextTags.length > 0 ? { tags: nextTags } : {}),
            },
            {
                preserveState: true,
                replace: true,
                async: true,
                showProgress: false,
                only: ['products', 'filters'],
                onFinish: () => {
                    if (visitId.current === id) {
                        setUpdating(false);
                    }
                },
            },
        );
    };

    const updateSidebarFilters = (partial: Partial<ShopSidebarFilters>) => {
        const next: ShopSidebarFilters = {
            minPrice:
                partial.minPrice !== undefined
                    ? partial.minPrice
                    : sidebar.minPrice,
            maxPrice:
                partial.maxPrice !== undefined
                    ? partial.maxPrice
                    : sidebar.maxPrice,
            categories:
                partial.categories !== undefined
                    ? partial.categories
                    : sidebar.categories,
            tags: partial.tags !== undefined ? partial.tags : sidebar.tags,
        };

        setSidebar(next);
        visit(next);
    };

    const filtersSidebar = (
        <ShopFiltersSidebar
            value={sidebar}
            onChange={updateSidebarFilters}
            categories={filterOptions.categories}
            tags={filterOptions.tags}
            priceRanges={filterOptions.priceRanges}
            sliderMin={filterOptions.sliderMin}
            sliderMax={filterOptions.sliderMax}
            className={
                isBelowLg ? undefined : 'lg:sticky lg:top-28 lg:self-start'
            }
        />
    );

    return (
        <>
            <Head title="Shop" />

            <div className="container mx-auto px-4 py-8 sm:px-6 sm:py-10">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-foreground text-2xl font-medium tracking-tight uppercase sm:text-[40px]">
                            Shop the collection
                        </h1>
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal uppercase">
                            Search, filter, and sort the full catalog
                        </p>
                    </div>
                </div>
                <div className="mb-8 flex w-full flex-col gap-3 lg:flex-row lg:items-center">
                    {isBelowLg ? (
                        <Sheet open={filtersOpen} onOpenChange={setFiltersOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="shrink-0 gap-2 self-start"
                                    aria-label="Open filters"
                                >
                                    <Menu className="size-5" />
                                    Filters
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="w-[min(100%,20rem)] overflow-y-auto"
                            >
                                <SheetHeader>
                                    <SheetTitle>Filters</SheetTitle>
                                </SheetHeader>
                                <div className="mt-6 px-4 pb-6">
                                    {filtersSidebar}
                                </div>
                            </SheetContent>
                        </Sheet>
                    ) : null}

                    <StorefrontSearchBar
                        value={search}
                        onChange={setSearch}
                        onSubmit={() => visit({ search })}
                        className="lg:min-w-0 lg:flex-1"
                    />
                    <StorefrontSortSelect
                        value={sort}
                        onChange={(nextSort) => {
                            setSort(nextSort);
                            visit({ sort: nextSort });
                        }}
                        className="lg:w-44 lg:max-w-xs lg:shrink-0"
                    />
                </div>

                <div className="grid items-start gap-8 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
                    {!isBelowLg ? filtersSidebar : null}

                    <div
                        aria-busy={updating}
                        className={
                            updating
                                ? 'pointer-events-none opacity-60 transition-opacity'
                                : 'transition-opacity'
                        }
                    >
                        {updating ? (
                            <p className="text-muted-foreground mb-3 text-xs font-semibold tracking-[0.2em] uppercase">
                                Updating results
                            </p>
                        ) : null}
                        {products.length === 0 ? (
                            <div className="border-border bg-card rounded-xl border p-10 text-center">
                                <p className="text-foreground text-base font-semibold">
                                    No products match
                                </p>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Try a different search or clear some
                                    filters.
                                </p>
                            </div>
                        ) : (
                            <div className="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {products.map((product) => (
                                    <ProductCard
                                        key={product.id}
                                        product={product}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
