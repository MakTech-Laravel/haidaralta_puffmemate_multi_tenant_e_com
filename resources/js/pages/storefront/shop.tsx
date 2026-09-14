import { Head, router } from '@inertiajs/react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import { StorefrontBranchSelect } from '@/components/storefront/storefront-branch-select';
import {
    storefrontFeatures,
    type StorefrontProduct,
} from '@/lib/storefront-demo';
import { shop } from '@/routes';

type ShopPageProps = {
    products: StorefrontProduct[];
    branches: Array<{ id: string; name: string }>;
    filters: {
        branchId: string | null;
    };
};

export default function StorefrontShop({
    products,
    branches,
    filters,
}: ShopPageProps) {
    return (
        <>
            <Head title="Shop" />

            <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal uppercase">
                            Browse the catalog
                        </p>
                        <h1 className="text-foreground text-2xl font-medium tracking-tight uppercase sm:text-[40px]">
                            Shop
                        </h1>
                    </div>
                    <StorefrontBranchSelect
                        branches={branches}
                        value={filters.branchId}
                        onChange={(branchId) => {
                            router.get(
                                shop.url({
                                    query: {
                                        ...(branchId
                                            ? { branch: branchId }
                                            : {}),
                                    },
                                }),
                                {},
                                { preserveState: true, replace: true },
                            );
                        }}
                        className="sm:w-72"
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
