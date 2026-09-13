import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import { StorefrontBranchSelect } from '@/components/storefront/storefront-branch-select';
import { StorefrontSearchBar } from '@/components/storefront/storefront-search-bar';
import { storefrontFeatures } from '@/lib/storefront-demo';
import type { StorefrontProduct } from '@/lib/storefront-demo';

type CategoryPageProps = {
    category: {
        slug: string;
        name: string;
    };
    products: StorefrontProduct[];
    branches: Array<{ id: string; name: string }>;
    filters: {
        search: string;
        branchId: string | null;
    };
};

export default function StorefrontCategory({
    category,
    products,
    branches,
    filters,
}: CategoryPageProps) {
    const [search, setSearch] = useState(filters.search);
    const [branchId, setBranchId] = useState<string | null>(filters.branchId);

    return (
        <>
            <Head title={category.name} />

            <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                <div className="mb-8 flex flex-col gap-3 lg:flex-row lg:items-stretch">
                    <StorefrontSearchBar
                        value={search}
                        onChange={setSearch}
                        className="lg:flex-2"
                    />
                    <StorefrontBranchSelect
                        branches={branches}
                        value={branchId}
                        onChange={setBranchId}
                        className="lg:flex-1"
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-5">
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>

                <div className="mt-10 flex flex-col items-center gap-2">
                    <div className="flex items-center gap-1.5" aria-hidden>
                        <span className="bg-primary size-2 rounded-full" />
                        <span className="bg-primary size-2 rounded-full opacity-70" />
                        <span className="bg-primary/30 size-2 rounded-full" />
                    </div>
                    <p className="text-foreground text-sm font-medium">Loading</p>
                </div>
            </div>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
