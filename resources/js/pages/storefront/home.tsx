import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { storefrontProducts } from '@/lib/storefront-demo';
import { shop } from '@/routes';

function ProductCard({
    name,
    price,
    category,
}: {
    name: string;
    price: string;
    category: string;
}) {
    return (
        <div className="border-border bg-card flex flex-col gap-2 rounded-lg border p-4">
            <div className="bg-muted aspect-[4/3] rounded-md" />
            <span className="bg-accent text-accent-foreground w-fit rounded px-1.5 py-0.5 text-xs">
                {category}
            </span>
            <p className="text-sm font-medium">{name}</p>
            <p className="text-muted-foreground text-sm">{price}</p>
        </div>
    );
}

export default function StorefrontHome() {
    const { name: brand } = usePage().props;

    return (
        <>
            <Head title="Home" />

            <section className="border-border border-b">
                <div className="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-16 sm:px-6 sm:py-20">
                    <p className="text-primary text-sm font-semibold tracking-wide uppercase">
                        {brand}
                    </p>
                    <h1 className="max-w-xl text-3xl font-semibold tracking-tight sm:text-4xl">
                        Cigarettes &amp; accessories, ready for pickup
                    </h1>
                    <p className="text-muted-foreground max-w-lg text-base">
                        Browse packs and essentials from your local branch.
                        Temporary placeholder until the Figma storefront lands.
                    </p>
                    <div>
                        <Button asChild size="lg">
                            <Link href={shop()} prefetch>
                                Shop now
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                <div className="mb-6 flex items-end justify-between gap-4">
                    <h2 className="text-lg font-semibold">Featured</h2>
                    <Link
                        href={shop()}
                        className="text-muted-foreground hover:text-foreground text-sm"
                    >
                        View shop
                    </Link>
                </div>
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {storefrontProducts.map((product) => (
                        <ProductCard key={product.id} {...product} />
                    ))}
                </div>
            </section>

            <section className="bg-muted/50 border-border border-t">
                <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                    <h2 className="mb-2 text-lg font-semibold">Accessories</h2>
                    <p className="text-muted-foreground max-w-md text-sm">
                        Lighters, cases, and everyday carry — stocked beside the
                        packs at each branch.
                    </p>
                </div>
            </section>
        </>
    );
}
