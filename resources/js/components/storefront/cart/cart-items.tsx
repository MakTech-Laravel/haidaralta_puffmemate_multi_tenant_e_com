import { Link } from '@inertiajs/react';
import { Minus, Plus, Trash2 } from 'lucide-react';
import { removeCartItem, syncCartQuantity } from '@/components/storefront/cart/sync-cart-item';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';
import { show as productShow } from '@/routes/products';
import type { StorefrontCartItem } from '@/types/storefront';

type CartItemsProps = {
    items: StorefrontCartItem[];
    className?: string;
};

export function CartItems({ items, className }: CartItemsProps) {
    if (items.length === 0) {
        return (
            <div
                className={cn(
                    'border-border bg-background rounded-xl border p-8 text-center',
                    className,
                )}
            >
                <p className="text-foreground font-semibold">
                    Your cart is empty
                </p>
                <p className="text-muted-foreground mt-1 text-sm">
                    Add products from the shop to get started.
                </p>
            </div>
        );
    }

    return (
        <div className={cn('flex min-w-0 flex-col gap-3', className)}>
            <ul className="flex min-w-0 flex-col gap-3">
                {items.map((item) => (
                    <li
                        key={item.id}
                        className="border-border bg-background flex w-full min-w-0 flex-col gap-4 overflow-hidden border p-3 sm:p-4 xl:flex-row xl:items-center xl:gap-6"
                    >
                        <div className="flex min-w-0 flex-1 gap-3">
                            <Link
                                href={productShow.url(item.slug)}
                                className="bg-muted relative size-16 shrink-0 overflow-hidden rounded-lg sm:size-20"
                            >
                                <img
                                    src={item.image}
                                    alt={item.name}
                                    className="size-full object-cover"
                                />
                            </Link>
                            <div className="min-w-0 flex-1">
                                <p className="text-muted-foreground text-xs font-extrabold uppercase">
                                    {item.category}
                                </p>
                                <Link
                                    href={productShow.url(item.slug)}
                                    className="text-foreground text-sm font-bold break-words sm:text-base"
                                >
                                    {item.name}
                                </Link>
                                {item.description ? (
                                    <p className="text-muted-foreground mt-0.5 text-xs font-normal break-words sm:text-sm">
                                        {item.description}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <div className="flex w-full min-w-0 items-center justify-between gap-3 xl:max-w-sm xl:shrink-0">
                            <p className="text-primary shrink-0 text-base font-bold">
                                {item.formattedLineTotal}
                            </p>
                            <div className="flex h-9 shrink-0 items-center gap-3">
                                <div className="border-primary/50 flex h-9 items-center border">
                                    <button
                                        type="button"
                                        className="text-foreground flex size-9 items-center justify-center"
                                        aria-label="Decrease quantity"
                                        onClick={() =>
                                            syncCartQuantity(
                                                item.id,
                                                item.quantity - 1,
                                            )
                                        }
                                    >
                                        <Minus className="size-3.5" />
                                    </button>
                                    <span className="text-foreground min-w-8 text-center text-sm font-semibold">
                                        {item.quantity}
                                    </span>
                                    <button
                                        type="button"
                                        className="text-foreground flex size-9 items-center justify-center"
                                        aria-label="Increase quantity"
                                        onClick={() =>
                                            syncCartQuantity(
                                                item.id,
                                                item.quantity + 1,
                                            )
                                        }
                                    >
                                        <Plus className="size-3.5" />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    className="text-muted-foreground hover:text-destructive flex size-9 shrink-0 items-center justify-center"
                                    aria-label={`Remove ${item.name}`}
                                    onClick={() => removeCartItem(item.id)}
                                >
                                    <Trash2 className="size-4" />
                                </button>
                            </div>
                        </div>
                    </li>
                ))}
            </ul>

            <Link
                href={shop.url()}
                prefetch
                className="text-primary inline-flex items-center gap-1 text-sm font-semibold hover:underline"
            >
                <Plus className="size-4" />
                Add another item
            </Link>
        </div>
    );
}
