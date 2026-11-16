import { Link } from '@inertiajs/react';
import { EyeIcon, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { addToCart } from '@/components/storefront/cart/sync-cart-item';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type {
    StorefrontProduct,
    StorefrontProductPackage,
} from '@/types/storefront';
import { cn } from '@/lib/utils';
import { show as productShow } from '@/routes/products';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type ProductCardProps = {
    product: StorefrontProduct;
    className?: string;
};

function packageTitle(pkg: StorefrontProductPackage): string {
    const quantityLabel = `${pkg.quantity} ${pkg.quantity === 1 ? 'Item' : 'Items'}`;

    if (/^\d+$/.test(pkg.label.trim())) {
        return quantityLabel;
    }

    return pkg.label;
}

export function ProductCard({ product, className }: ProductCardProps) {
    const hasOffer = Boolean(product.compareAtPrice);
    const imageSrc = product.image || FALLBACK_IMAGE;
    const packages = product.packages ?? [];
    const needsPackage = packages.length > 0;
    const [adding, setAdding] = useState(false);
    const [chooserOpen, setChooserOpen] = useState(false);
    const [packageId, setPackageId] = useState<number | null>(null);
    const [addedViews, setAddedViews] = useState(0);
    const displayViews = /^\d+$/.test(product.views)
        ? String(Number(product.views) + addedViews)
        : product.views;

    function add(selectedPackageId?: number): void {
        setAdding(true);
        addToCart(product.id, 1, () => setAdding(false), {
            countView: true,
            productPackageId: selectedPackageId,
            onSuccess: (page) => {
                setChooserOpen(false);

                if (page.flash?.view_counted === true) {
                    setAddedViews((count) => count + 1);
                }
            },
        });
    }

    return (
        <article
            className={cn(
                'border-border bg-card flex h-full flex-col overflow-hidden rounded-2xl border transition hover:shadow-lg',
                className,
            )}
        >
            <Link
                href={productShow(product.id)}
                className="flex min-w-0 flex-1 flex-col"
            >
                <div className="bg-muted relative aspect-3/2 overflow-hidden">
                    {hasOffer ? (
                        <span className="bg-ink-surface/80 text-ink-surface-foreground absolute top-2 right-2 z-10 rounded-full px-2 py-0.5 text-xs font-medium backdrop-blur-sm sm:top-3 sm:right-3 sm:px-2.5 sm:py-1">
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
                        className="bg-ink-surface/80 text-ink-surface-foreground absolute right-2 bottom-2 z-10 flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium backdrop-blur-sm sm:right-3 sm:bottom-3 sm:px-2.5 sm:py-1"
                        aria-label={`${displayViews} views`}
                    >
                        <EyeIcon className="size-3.5 sm:size-4" />
                        <span>{displayViews}</span>
                    </div>
                </div>
                <div className="flex flex-1 flex-col gap-1 p-2.5 pb-2 sm:p-4">
                    <h3 className="text-foreground min-w-0 truncate text-sm font-semibold tracking-tight sm:text-lg">
                        {product.name}
                    </h3>
                    <p className="text-muted-foreground truncate text-xs font-normal sm:text-sm">
                        {product.description}
                    </p>
                    <div className="mt-1 flex flex-wrap items-baseline gap-2 sm:mt-2">
                        <p className="text-foreground text-base font-semibold sm:text-lg">
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
            <div className="px-2.5 pb-2.5 sm:px-4 sm:pb-4">
                <Button
                    type="button"
                    size="sm"
                    className="h-11 w-full text-xs font-semibold sm:text-sm"
                    disabled={adding}
                    onClick={() => {
                        if (!needsPackage) {
                            add();

                            return;
                        }

                        setPackageId(packages[0]?.id ?? null);
                        setChooserOpen(true);
                    }}
                >
                    <ShoppingCart className="size-3.5" />
                    {adding
                        ? 'Adding...'
                        : needsPackage
                          ? 'Choose package'
                          : 'Add to cart'}
                </Button>
            </div>
            {needsPackage ? (
                <Dialog open={chooserOpen} onOpenChange={setChooserOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Choose a package</DialogTitle>
                            <DialogDescription>{product.name}</DialogDescription>
                        </DialogHeader>
                        <ul className="flex max-h-72 flex-col gap-2 overflow-y-auto">
                            {packages.map((pkg) => {
                                const selected = packageId === pkg.id;
                                const title = packageTitle(pkg);
                                const quantityLabel = `${pkg.quantity} ${
                                    pkg.quantity === 1 ? 'Item' : 'Items'
                                }`;

                                return (
                                    <li key={pkg.id}>
                                        <button
                                            type="button"
                                            onClick={() => setPackageId(pkg.id)}
                                            className={cn(
                                                'bg-background flex w-full items-center justify-between gap-3 rounded-2xl border-2 px-4 py-3 text-left',
                                                selected
                                                    ? 'border-primary'
                                                    : 'border-border',
                                            )}
                                            aria-pressed={selected}
                                        >
                                            <span className="min-w-0">
                                                <span className="text-foreground block text-sm font-semibold">
                                                    {title}
                                                </span>
                                                {title !== quantityLabel ? (
                                                    <span className="text-muted-foreground block text-xs">
                                                        {quantityLabel}
                                                    </span>
                                                ) : null}
                                            </span>
                                            <span className="text-foreground shrink-0 text-sm font-semibold">
                                                {pkg.formattedPrice}
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                        <DialogFooter>
                            <Button
                                type="button"
                                className="w-full sm:w-auto"
                                disabled={adding || packageId === null}
                                onClick={() => {
                                    if (packageId !== null) {
                                        add(packageId);
                                    }
                                }}
                            >
                                <ShoppingCart className="size-4" />
                                {adding ? 'Adding...' : 'Add to cart'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            ) : null}
        </article>
    );
}
