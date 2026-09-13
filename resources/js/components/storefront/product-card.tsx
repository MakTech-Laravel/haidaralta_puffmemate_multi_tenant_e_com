import type { StorefrontProduct } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';

type ProductCardProps = {
    product: StorefrontProduct;
    className?: string;
};

export function ProductCard({ product, className }: ProductCardProps) {
    return (
        <article
            className={cn(
                'border-border bg-card flex flex-col overflow-hidden rounded-xl border',
                className,
            )}
        >
            <div className="bg-muted relative aspect-square">
                <span className="bg-background/80 text-foreground absolute top-3 left-3 z-10 rounded-full px-2.5 py-1 text-xs font-medium backdrop-blur-sm">
                    {product.category}
                </span>
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
                <h3 className="text-foreground text-base font-semibold sm:text-lg">
                    {product.name}
                </h3>
                <p className="text-muted-foreground text-sm font-normal">
                    {product.description}
                </p>
                <p className="text-primary mt-2 text-lg font-semibold">
                    {product.price}
                </p>
                <p className="text-muted-foreground text-sm font-normal">
                    Available stock: {product.stock} pcs
                </p>
            </div>
        </article>
    );
}
