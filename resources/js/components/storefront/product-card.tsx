import { Link } from '@inertiajs/react';
import { EyeIcon, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { addToCart } from '@/components/storefront/cart/sync-cart-item';
import { Button } from '@/components/ui/button';
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
    const [adding, setAdding] = useState(false);
    const [addedViews, setAddedViews] = useState(0);
    const displayViews = /^\d+$/.test(product.views)
        ? String(Number(product.views) + addedViews)
        : product.views;

    return (
        <article
            className={cn(
                'border-border bg-card hover:border-primary/40 flex h-full flex-col overflow-hidden rounded-xl border transition',
                className,
            )}
        >
            <Link
                href={productShow(product.id)}
                className="flex min-w-0 flex-1 flex-col"
            >
                <div className="bg-muted relative aspect-3/2 overflow-hidden">
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
                    <div
                        className="absolute right-2 bottom-2 z-10 flex items-center gap-1 rounded-full bg-black/50 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm sm:right-3 sm:bottom-3 sm:px-2.5 sm:py-1"
                        aria-label={`${displayViews} views`}
                    >
                        <EyeIcon className="size-3.5 sm:size-4" />
                        <span>{displayViews}</span>
                    </div>
                </div>
                <div className="flex flex-1 flex-col gap-1 p-4 pb-2">
                    <h3 className="text-foreground min-w-0 truncate text-base font-semibold sm:text-lg">
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
            <div className="px-4 pb-4">
                <Button
                    type="button"
                    size="sm"
                    className="h-9 w-full rounded-none text-xs font-bold tracking-wide uppercase"
                    disabled={adding}
                    onClick={() => {
                        setAdding(true);
                        addToCart(product.id, 1, () => setAdding(false), {
                            countView: true,
                            onSuccess: () => setAddedViews((count) => count + 1),
                        });
                    }}
                >
                    <ShoppingCart className="size-3.5" />
                    {adding ? 'Adding...' : 'Add to cart'}
                </Button>
            </div>
        </article>
    );
}
