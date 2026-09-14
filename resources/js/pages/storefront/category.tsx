import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import { StorefrontBranchSelect } from '@/components/storefront/storefront-branch-select';
import { StorefrontSearchBar } from '@/components/storefront/storefront-search-bar';
import { StorefrontSortSelect } from '@/components/storefront/storefront-sort-select';
import { storefrontFeatures } from '@/lib/storefront-demo';
import type { StorefrontProduct } from '@/lib/storefront-demo';
import { category as shopCategory } from '@/routes/shop';

type CategoryPageProps = {
    category: {
        slug: string;
        name: string;
        slogan?: string | null;
    };
    products: StorefrontProduct[];
    branches: Array<{ id: string; name: string }>;
    filters: {
        search: string;
        branchId: string | null;
        sort: string | null;
    };
};

export default function StorefrontCategory({
    category,
    products,
    branches,
    filters,
}: CategoryPageProps) {
    const [search, setSearch] = useState(filters.search);

    const visit = (next: {
        search?: string;
        branchId?: string | null;
        sort?: string | null;
    }) => {
        const nextSearch = next.search ?? search;
        const nextBranch =
            next.branchId !== undefined ? next.branchId : filters.branchId;
        const nextSort = next.sort !== undefined ? next.sort : filters.sort;

        router.get(
            shopCategory.url(category.slug),
            {
                ...(nextSearch ? { search: nextSearch } : {}),
                ...(nextBranch ? { branch: nextBranch } : {}),
                ...(nextSort ? { sort: nextSort } : {}),
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title={category.name} />

            <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                <div className="mb-8 flex flex-col gap-3 lg:flex-row lg:items-stretch">
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

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </div>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
