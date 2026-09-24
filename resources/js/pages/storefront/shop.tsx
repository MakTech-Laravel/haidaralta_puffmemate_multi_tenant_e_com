import { Head, router } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState, useSyncExternalStore } from 'react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import {
    ShopFiltersSidebar,
    type ShopSidebarFilters,
} from '@/components/storefront/shop-filters-sidebar';
import { StorefrontBranchSelect } from '@/components/storefront/storefront-branch-select';
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

const emptySidebarFilters: ShopSidebarFilters = {
    minPrice: null,
    maxPrice: null,
    categories: [],
    types: [],
};

type ShopPageProps = {
    products: StorefrontProduct[];
    branches: Array<{ id: string; name: string }>;
    filters: {
        search: string;
        branchId: string | null;
        sort: string | null;
    };
};

type VisitOptions = {
    search?: string;
    branchId?: string | null;
    sort?: string | null;
};

export default function StorefrontShop({
    products,
    branches,
    filters,
}: ShopPageProps) {
    const isBelowLg = useIsBelowLg();
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [search, setSearch] = useState(filters.search);
    const [sidebarFilters, setSidebarFilters] =
        useState<ShopSidebarFilters>(emptySidebarFilters);

    const updateSidebarFilters = (partial: Partial<ShopSidebarFilters>) => {
        setSidebarFilters((current) => ({
            ...current,
            ...partial,
        }));
    };

    const visit = (next: VisitOptions = {}) => {
        const nextSearch = next.search ?? search;
        const nextBranch =
            next.branchId !== undefined ? next.branchId : filters.branchId;
        const nextSort = next.sort !== undefined ? next.sort : filters.sort;

        router.cancelAll();
        router.get(
            shop.url(),
            {
                ...(nextSearch ? { search: nextSearch } : {}),
                ...(nextBranch ? { branch: nextBranch } : {}),
                ...(nextSort ? { sort: nextSort } : {}),
            },
            {
                preserveState: true,
                replace: true,
                only: ['products', 'filters', 'branches'],
            },
        );
    };

    const filtersSidebar = (
        <ShopFiltersSidebar
            value={sidebarFilters}
            onChange={updateSidebarFilters}
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
                            Choose a product first, then compare which branches
                            have it in stock
                        </p>
                    </div>
                </div>
                <div className="mb-8 flex w-full flex-col gap-3 lg:flex-row lg:items-stretch">
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
                        className="lg:min-w-0 lg:flex-[1.4]"
                    />
                    <StorefrontBranchSelect
                        branches={branches}
                        value={filters.branchId}
                        onChange={(branchId) => visit({ branchId })}
                        className="lg:min-w-0 lg:flex-1"
                    />
                    <StorefrontSortSelect
                        value={filters.sort}
                        onChange={(sort) => visit({ sort })}
                        className="lg:min-w-0 lg:flex-1"
                    />
                </div>

                <div className="grid items-start gap-8 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
                    {!isBelowLg ? filtersSidebar : null}

                    <div className="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {products.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                </div>
            </div>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
