import { Minus, Plus } from 'lucide-react';
import type { DriverPlaceOrderProduct } from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type PlaceOrderProductsSectionProps = {
    products: DriverPlaceOrderProduct[];
    quantities: Record<string, number>;
    onQuantityChange: (productId: string, quantity: number) => void;
};

export function PlaceOrderProductsSection({
    products,
    quantities,
    onQuantityChange,
}: PlaceOrderProductsSectionProps) {
    return (
        <ul className="space-y-2.5">
            {products.map((product) => {
                const outOfStock = product.stock < 1;
                const quantity = quantities[product.id] ?? 0;
                const selected = quantity > 0;

                return (
                    <li
                        key={product.id}
                        className={cn(
                            'border-border flex gap-3 rounded-2xl border p-3 transition-colors',
                            outOfStock && 'opacity-55',
                            selected &&
                                !outOfStock &&
                                'border-primary bg-primary/5',
                        )}
                    >
                        <div className="bg-muted relative size-16 shrink-0 overflow-hidden rounded-xl sm:size-[4.5rem]">
                            <img
                                src={product.image || FALLBACK_IMAGE}
                                alt=""
                                className="absolute inset-0 size-full object-cover"
                                onError={(event) => {
                                    event.currentTarget.src = FALLBACK_IMAGE;
                                }}
                            />
                        </div>

                        <div className="min-w-0 flex-1 space-y-2.5">
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0 space-y-1">
                                    <p className="text-foreground truncate text-sm font-semibold">
                                        {product.name}
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {product.description}
                                    </p>
                                    <span
                                        className={cn(
                                            'inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase',
                                            outOfStock
                                                ? 'bg-destructive/10 text-destructive'
                                                : 'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {outOfStock
                                            ? 'Out of stock'
                                            : `${product.stock} left`}
                                    </span>
                                </div>
                                <p className="text-foreground shrink-0 text-sm font-bold tabular-nums">
                                    {product.priceLabel}
                                </p>
                            </div>

                            {outOfStock ? (
                                <p className="text-muted-foreground text-xs font-semibold">
                                    Unavailable
                                </p>
                            ) : (
                                <div className="bg-muted/70 inline-flex items-center gap-1 rounded-full p-1">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="bg-background size-8 rounded-full shadow-sm"
                                        disabled={quantity < 1}
                                        aria-label={`Decrease ${product.name}`}
                                        onClick={() =>
                                            onQuantityChange(
                                                product.id,
                                                Math.max(0, quantity - 1),
                                            )
                                        }
                                    >
                                        <Minus className="size-3.5" />
                                    </Button>
                                    <span className="text-foreground w-7 text-center text-sm font-bold tabular-nums">
                                        {quantity}
                                    </span>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        className="bg-background size-8 rounded-full shadow-sm"
                                        disabled={quantity >= product.stock}
                                        aria-label={`Increase ${product.name}`}
                                        onClick={() =>
                                            onQuantityChange(
                                                product.id,
                                                Math.min(
                                                    product.stock,
                                                    quantity + 1,
                                                ),
                                            )
                                        }
                                    >
                                        <Plus className="size-3.5" />
                                    </Button>
                                </div>
                            )}
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
