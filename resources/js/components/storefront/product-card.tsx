import { Link } from '@inertiajs/react';
import type { StorefrontProduct } from '@/types/storefront';
import { cn } from '@/lib/utils';
import { show as productShow } from '@/routes/products';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type ProductCardProps = {
    product: StorefrontProduct;
    className?: string;
};

export function ProductCard({ product, className }: ProductCardProps) {
    const hasOffer = Boolean(product.compareAtPrice);
    const imageSrc = product.image || FALLBACK_IMAGE;

    return (
        <Link
            href={productShow(product.id)}
            prefetch
            className={cn(
                'border-border bg-card hover:border-primary/40 flex flex-col overflow-hidden rounded-xl border transition',
                className,
            )}
        >
            <div className="bg-muted relative aspect-[3/2] overflow-hidden">
                {hasOffer ? (
                    <span className="absolute top-2 right-2 z-10 rounded-full bg-black/50 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm sm:top-3 sm:right-3 sm:px-2.5 sm:py-1">
                        Offer Product
                    </span>
                ) : null}
                <img
                    src={imageSrc}
                    alt={product.name}
                    loading="lazy"
                    decoding="async"
                    sizes="(max-width: 768px) 50vw, (max-width: 1280px) 33vw, 25vw"
                    className="absolute inset-0 size-full object-cover"
                    onError={(event) => {
                        event.currentTarget.src = FALLBACK_IMAGE;
                    }}
                />
            </div>
            <div className="flex flex-1 flex-col gap-1 p-4">
                <h3 className="text-foreground truncate text-base font-semibold sm:text-lg">
                    {product.name}
                </h3>
                <p className="text-muted-foreground truncate text-sm font-normal">
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
            </div>
        </Link>
    );
}
