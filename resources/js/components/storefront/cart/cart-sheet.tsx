import { Link, router } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    CART_OPEN_EVENT,
    removeCartItem,
    syncCartQuantity,
} from '@/components/storefront/cart/sync-cart-item';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
import { cart, checkout } from '@/routes';
import { show as productShow } from '@/routes/products';
import type { StorefrontCart } from '@/types/storefront';
import { cn } from '@/lib/utils';

type CartSheetProps = {
    cart: StorefrontCart;
    triggerClassName?: string;
    active?: boolean;
};

export function CartSheet({
    cart: cartData,
    triggerClassName,
    active = false,
}: CartSheetProps) {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const openSheet = () => setOpen(true);
        window.addEventListener(CART_OPEN_EVENT, openSheet);

        const stop = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (
                visit.async === true ||
                visit.prefetch === true ||
                (visit.only?.length ?? 0) > 0 ||
                (visit.except?.length ?? 0) > 0
            ) {
                return;
            }

            setOpen(false);
        });

        return () => {
            window.removeEventListener(CART_OPEN_EVENT, openSheet);
            stop();
        };
    }, []);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger
                className={cn(
                    'relative inline-flex size-10 items-center justify-center rounded-full transition-colors',
                    triggerClassName,
                    active ? 'bg-muted text-foreground' : 'text-foreground hover:bg-muted/70',
                )}
                aria-label="Cart"
            >
                <ShoppingCart className="size-5 md:size-[22px]" strokeWidth={1.75} />
                {cartData.itemCount > 0 ? (
                    <span className="bg-primary text-primary-foreground absolute top-0 right-0 flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] font-semibold">
                        {cartData.itemCount}
                    </span>
                ) : null}
            </SheetTrigger>
            <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-md">
                <SheetHeader className="border-border border-b">
                    <SheetTitle className="text-xl font-semibold tracking-tight">
                        Your cart ({cartData.itemCount})
                    </SheetTitle>
                </SheetHeader>
                <div className="flex-1 space-y-4 overflow-y-auto px-4 py-4">
                    {cartData.items.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            Your cart is empty.
                        </p>
                    ) : (
                        cartData.items.map((item) => (
                            <div
                                key={item.id}
                                className="border-border flex gap-3 border-b pb-4 last:border-b-0 last:pb-0"
                            >
                                <Link
                                    href={productShow.url(item.slug)}
                                    className="bg-muted size-20 shrink-0 overflow-hidden rounded-2xl"
                                >
                                    <StorefrontProductImage
                                        src={item.image}
                                        alt={item.name}
                                    />
                                </Link>
                                <div className="min-w-0 flex-1">
                                    <p className="text-foreground text-sm font-semibold break-words">
                                        {item.name}
                                    </p>
                                    {item.packageLabel ? (
                                        <p className="text-muted-foreground mt-0.5 text-xs">
                                            {item.packageLabel}
                                            {item.packageQuantity
                                                ? ` · ${item.packageQuantity} ${item.packageQuantity === 1 ? 'item' : 'items'}`
                                                : ''}
                                        </p>
                                    ) : item.description ? (
                                        <p className="text-muted-foreground mt-0.5 text-xs">
                                            {item.description}
                                        </p>
                                    ) : null}
                                    <p className="mt-1 text-sm">
                                        <span className="text-foreground font-semibold">
                                            {item.formattedUnitPrice}
                                        </span>
                                        {item.formattedCompareAtUnitPrice ? (
                                            <span className="text-muted-foreground ml-1.5 text-xs line-through">
                                                {
                                                    item.formattedCompareAtUnitPrice
                                                }
                                            </span>
                                        ) : null}
                                        <span className="text-muted-foreground text-xs">
                                            {' '}
                                            each
                                        </span>
                                    </p>
                                    <div className="mt-3 flex items-center gap-3">
                                        <div className="bg-muted flex h-9 items-center rounded-full">
                                            <button
                                                type="button"
                                                className="text-foreground flex size-9 items-center justify-center"
                                                aria-label="Decrease quantity"
                                                onClick={() =>
                                                    syncCartQuantity(
                                                        item,
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
                                                className="text-foreground flex size-9 items-center justify-center disabled:cursor-not-allowed disabled:opacity-40"
                                                aria-label="Increase quantity"
                                                disabled={
                                                    item.quantity >=
                                                    item.maxQuantity
                                                }
                                                onClick={() =>
                                                    syncCartQuantity(
                                                        item,
                                                        item.quantity + 1,
                                                    )
                                                }
                                            >
                                                <Plus className="size-3.5" />
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            className="text-muted-foreground hover:text-destructive ml-auto flex size-9 items-center justify-center"
                                            aria-label={`Remove ${item.name}`}
                                            onClick={() =>
                                                removeCartItem(item.id)
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
                <SheetFooter className="border-border bg-muted gap-3 border-t">
                    <div className="flex w-full items-end justify-between">
                        <span className="text-foreground text-sm font-semibold">
                            Subtotal
                        </span>
                        <span className="text-foreground text-xl font-semibold tracking-tight">
                            {cartData.formattedSubtotal}
                        </span>
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        className="h-12 w-full text-sm font-semibold"
                    >
                        <Link
                            href={cart.url()}
                            onClick={() => setOpen(false)}
                        >
                            View cart
                        </Link>
                    </Button>
                    <Button
                        asChild
                        className="h-12 w-full text-sm font-semibold"
                    >
                        <Link
                            href={checkout.url()}
                            onClick={() => setOpen(false)}
                        >
                            Checkout
                        </Link>
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
