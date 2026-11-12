import { Link } from '@inertiajs/react';
import { EyeIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { show as productShow } from '@/routes/products';
import type { StorefrontPackagedProduct } from '@/types/storefront';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type PackageProductCardProps = {
    product: StorefrontPackagedProduct;
    className?: string;
};

export function PackageProductCard({
    product,
    className,
}: PackageProductCardProps) {
    const imageSrc = product.image || FALLBACK_IMAGE;
    const packageLine =
        product.packageCount === 1
            ? product.fromLabel
            : `${product.packageCount} packages`;
    const priceLabel =
        product.packageCount > 1 ? `From ${product.price}` : product.price;

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
                        aria-label={`${product.views} views`}
                    >
                        <EyeIcon className="size-3.5 sm:size-4" />
                        <span>{product.views}</span>
                    </div>
                </div>
                <div className="flex flex-1 flex-col gap-1 p-4 pb-2">
                    <h3 className="text-foreground min-w-0 truncate text-base font-semibold sm:text-lg">
                        {product.name}
                    </h3>
                    <p className="text-muted-foreground truncate text-sm font-normal">
                        {product.description}
                    </p>
                    <p className="text-muted-foreground truncate text-xs">
                        {packageLine}
                    </p>
                    <p className="text-primary mt-2 text-lg font-semibold">
                        {priceLabel}
                    </p>
                </div>
            </Link>
            <div className="px-4 pb-4">
                <Button
                    asChild
                    size="sm"
                    className="h-10 w-full text-sm font-semibold"
                >
                    <Link href={productShow(product.id)}>Choose package</Link>
                </Button>
            </div>
        </article>
    );
}
