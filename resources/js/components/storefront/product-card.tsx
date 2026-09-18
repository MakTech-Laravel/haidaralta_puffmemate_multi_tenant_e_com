import { Link } from '@inertiajs/react';
import type { StorefrontProduct } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';
import { show as productShow } from '@/routes/products';

type ProductCardProps = {
    product: StorefrontProduct;
    className?: string;
};

export function ProductCard({ product, className }: ProductCardProps) {
    const hasOffer = Boolean(product.compareAtPrice);

    return (
        <Link
            href={productShow(product.id)}
            prefetch
            className={cn(
                'border-border bg-card flex flex-col overflow-hidden rounded-xl border transition hover:border-primary/40',
                className,
            )}
        >
            <div className="bg-muted relative aspect-[3/2]">
                {hasOffer ? (
                    <span className="absolute top-2 right-2 z-10 rounded-full bg-black/50 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm sm:top-3 sm:right-3 sm:px-2.5 sm:py-1">
                        Offer Product
                    </span>
                ) : null}
                <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{
                        backgroundImage: `url(${product.image})`,
                        backgroundColor: 'var(--muted)',
                    }}
                    role="img"
                    aria-label={product.name}
                />
            </div>
            <div className="flex flex-1 flex-col gap-1 p-4">
                <h3 className="text-foreground text-base font-semibold sm:text-lg truncate">
                    {product.name}
                </h3>
                <p className="text-muted-foreground text-sm font-normal truncate">
                    {product.description}
                </p>
                <div className="mt-2 flex flex-wrap items-baseline gap-2">
                    <p className="text-primary text-lg font-semibold">
                        {product.price}
                    </p>
                    {hasOffer ? (
                        <p className="text-muted-foreground text-sm font-normal line-through">
                            {product.compareAtPrice}
                        </p>
                    ) : null}
                </div>
                <p className="text-tertiary text-sm font-normal">
                    Available stock: {product.stock} pcs
                </p>
            </div>
        </Link>
    );
}
