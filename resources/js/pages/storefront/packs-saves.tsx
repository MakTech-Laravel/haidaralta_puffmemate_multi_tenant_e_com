import { Head } from '@inertiajs/react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { ProductCard } from '@/components/storefront/product-card';
import { storefrontFeatures } from '@/lib/storefront-features';
import type { StorefrontProduct } from '@/types/storefront';

type BranchOption = {
    id: string;
    name: string;
};

export default function StorefrontPacksSaves({
    products,
}: {
    products: StorefrontProduct[];
    branches: BranchOption[];
}) {
    return (
        <>
            <Head title="Packs & Saves" />

            <section className="border-border bg-muted/40 border-b">
                <div className="container mx-auto px-4 py-10 sm:px-6 sm:py-12">
                    <p className="text-muted-foreground text-xs font-semibold tracking-[0.2em] uppercase">
                        Bundles & multipacks
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Packs & Saves
                    </h1>
                    <p className="text-muted-foreground mt-3 max-w-2xl text-base">
                        Carton deals and multipack offers from nearby branches —
                        better value when you stock up.
                    </p>
                </div>
            </section>

            <FeaturesBar features={storefrontFeatures} />

            <section className="container mx-auto px-4 py-10 sm:px-6 sm:py-12">
                {products.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No packs or multipack offers right now. Check back soon
                        or browse the full shop.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                        {products.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </section>
        </>
    );
}
