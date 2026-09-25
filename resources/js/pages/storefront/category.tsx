import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import { StorefrontSearchBar } from '@/components/storefront/storefront-search-bar';
import { StorefrontSortSelect } from '@/components/storefront/storefront-sort-select';
import { storefrontFeatures } from '@/lib/storefront-features';
import { category as shopCategory } from '@/routes/shop';
import type { StorefrontProduct } from '@/types/storefront';

type CategoryPageProps = {
    category: {
        slug: string;
        name: string;
        slogan?: string | null;
    };
    products: StorefrontProduct[];
    filters: {
        search: string;
        sort: string | null;
    };
};

type VisitOptions = {
    search?: string;
    sort?: string | null;
};

export default function StorefrontCategory({
    category,
    products,
    filters,
}: CategoryPageProps) {
    const [search, setSearch] = useState(filters.search);
    const [sort, setSort] = useState(filters.sort);
    const [updating, setUpdating] = useState(false);
    const debounceRef = useRef<number>(undefined);
    const visitId = useRef(0);

    useEffect(() => {
        window.clearTimeout(debounceRef.current);
        setSearch(filters.search);
        setSort(filters.sort);
    }, [filters.search, filters.sort, category.slug]);

    const visit = (next: VisitOptions = {}) => {
        const nextSearch = next.search ?? search;
        const nextSort = next.sort !== undefined ? next.sort : sort;

        const id = visitId.current + 1;
        visitId.current = id;
        setUpdating(true);
        router.cancelAll();
        router.get(
            shopCategory.url(category.slug),
            {
                ...(nextSearch ? { search: nextSearch } : {}),
                ...(nextSort ? { sort: nextSort } : {}),
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

    const updateSearch = (next: string) => {
        setSearch(next);
        window.clearTimeout(debounceRef.current);
        debounceRef.current = window.setTimeout(() => {
            visit({ search: next });
        }, 300);
    };

    return (
        <>
            <Head title={category.name} />

            <div className="container mx-auto px-4 py-8 sm:px-6 sm:py-10">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-foreground text-2xl font-medium tracking-tight uppercase sm:text-[40px]">
                            {category.name}
                        </h1>
                        {category.slogan ? (
                            <p className="text-muted-foreground font-inter mb-1 text-sm font-normal uppercase">
                                {category.slogan}
                            </p>
                        ) : null}
                    </div>
                </div>

                <div className="mb-8 flex w-full flex-col gap-3 lg:flex-row lg:items-center">
                    <StorefrontSearchBar
                        value={search}
                        onChange={updateSearch}
                        onSubmit={() => {
                            window.clearTimeout(debounceRef.current);
                            visit({ search });
                        }}
                        className="lg:min-w-0 lg:flex-1"
                    />
                    <StorefrontSortSelect
                        value={sort}
                        onChange={(nextSort) => {
                            setSort(nextSort);
                            visit({ sort: nextSort });
                        }}
                        className="lg:w-56 lg:max-w-xs lg:shrink-0"
                    />
                </div>

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
                                Try a different search or sort.
                            </p>
                        </div>
                    ) : (
                        <div className="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
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

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
