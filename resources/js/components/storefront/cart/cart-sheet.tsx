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
                    'relative inline-flex size-10 items-center justify-center rounded-md transition-colors',
                    triggerClassName,
                    active ? 'text-primary' : 'text-foreground',
                )}
                aria-label="Cart"
            >
                <ShoppingCart className="size-6 md:size-8" strokeWidth={1.75} />
                {cartData.itemCount > 0 ? (
                    <span className="bg-primary text-primary-foreground absolute top-0.5 right-0.5 min-w-4 rounded-full px-1 text-[10px] font-bold">
                        {cartData.itemCount}
                    </span>
                ) : null}
            </SheetTrigger>
            <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-md">
                <SheetHeader className="border-border border-b">
                    <SheetTitle className="text-lg font-bold tracking-tight">
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
                                    className="bg-muted size-16 shrink-0 overflow-hidden rounded-lg"
                                >
                                    <StorefrontProductImage
                                        src={item.image}
                                        alt={item.name}
                                    />
                                </Link>
                                <div className="min-w-0 flex-1">
                                    <p className="text-foreground text-sm font-bold break-words">
                                        {item.name}
                                    </p>
                                    {item.description ? (
                                        <p className="text-muted-foreground mt-0.5 text-xs">
                                            {item.description}
                                        </p>
                                    ) : null}
                                    <p className="text-primary mt-1 text-sm font-bold">
                                        {item.formattedUnitPrice}
                                    </p>
                                    <div className="mt-3 flex items-center gap-3">
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
                <SheetFooter className="border-border gap-3 border-t">
                    <div className="flex w-full items-end justify-between">
                        <span className="text-foreground text-sm font-bold">
                            Subtotal
                        </span>
                        <span className="text-primary text-xl font-bold">
                            {cartData.formattedSubtotal}
                        </span>
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full rounded-none text-sm font-bold"
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
                        className="h-11 w-full rounded-none text-sm font-bold tracking-wide"
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
