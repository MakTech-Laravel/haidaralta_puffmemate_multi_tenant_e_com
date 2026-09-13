import { Head } from '@inertiajs/react';
import { ProductCard } from '@/components/storefront/product-card';
import { storefrontProducts } from '@/lib/storefront-demo';

export default function StorefrontShop() {
    return (
        <>
            <Head title="Shop" />

            <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <h1 className="mb-2 text-2xl font-semibold tracking-tight">
                    Shop
                </h1>
                <p className="text-muted-foreground mb-8 text-sm">
                    Static sample catalog — cigarettes, vapes, and accessories.
                </p>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {storefrontProducts.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </div>
        </>
    );
}
