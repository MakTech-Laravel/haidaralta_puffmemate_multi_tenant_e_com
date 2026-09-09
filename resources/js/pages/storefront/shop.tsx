import { Head } from '@inertiajs/react';
import { storefrontProducts } from '@/lib/storefront-demo';

export default function StorefrontShop() {
    return (
        <>
            <Head title="Shop" />

            <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                <h1 className="mb-2 text-2xl font-semibold tracking-tight">
                    Shop
                </h1>
                <p className="text-muted-foreground mb-8 text-sm">
                    Static sample catalog — cigarettes and accessories.
                </p>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {storefrontProducts.map((product) => (
                        <div
                            key={product.id}
                            className="border-border bg-card flex flex-col gap-2 rounded-lg border p-4"
                        >
                            <div className="bg-muted aspect-[4/3] rounded-md" />
                            <span className="bg-accent text-accent-foreground w-fit rounded px-1.5 py-0.5 text-xs">
                                {product.category}
                            </span>
                            <p className="text-sm font-medium">{product.name}</p>
                            <p className="text-muted-foreground text-sm">
                                {product.price}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
