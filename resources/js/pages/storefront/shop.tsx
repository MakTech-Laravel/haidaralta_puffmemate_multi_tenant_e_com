import { Head, router } from '@inertiajs/react';
import { ProductCard } from '@/components/storefront/product-card';
import { StorefrontBranchSelect } from '@/components/storefront/storefront-branch-select';
import type { StorefrontProduct } from '@/lib/storefront-demo';
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

            <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="mb-2 text-2xl font-semibold tracking-tight">
                            Shop
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Live catalog from your selected branch.
                        </p>
                    </div>
                    <StorefrontBranchSelect
                        branches={branches}
                        value={filters.branchId}
                        onChange={(branchId) => {
                            router.get(
                                shop.url({
                                    query: {
                                        ...(branchId ? { branch: branchId } : {}),
                                    },
                                }),
                                {},
                                { preserveState: true, replace: true },
                            );
                        }}
                        className="sm:w-72"
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {products.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </div>
        </>
    );
}
