import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CartItems } from '@/components/storefront/cart/cart-items';
import { CartOrderSummary } from '@/components/storefront/cart/cart-order-summary';
import { Button } from '@/components/ui/button';
import { checkout, shop } from '@/routes';
import type { StorefrontCart } from '@/types/storefront';

export default function StorefrontCart(cart: StorefrontCart) {
    const hasItems = cart.itemCount > 0;

    return (
        <>
            <Head title="Cart" />

            <div className="bg-muted flex flex-1 flex-col">
                <div className="container mx-auto flex w-full flex-1 flex-col px-4 py-6 sm:px-6 sm:py-8">
                    <Link
                        href={shop.url()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm font-semibold transition"
                    >
                        <ArrowLeft className="size-4" />
                        Continue shopping
                    </Link>

                    <div className="mt-4 mb-5">
                        <div className="flex flex-wrap items-baseline gap-3">
                            <h1 className="text-foreground text-2xl font-semibold tracking-tight sm:text-4xl">
                                Your cart
                            </h1>
                            <span className="text-muted-foreground text-lg font-medium">
                                {cart.itemCount}{' '}
                                {cart.itemCount === 1 ? 'item' : 'items'}
                            </span>
                        </div>
                    </div>

                    {hasItems ? (
                        <div className="grid min-w-0 gap-6 pb-[calc(5rem+env(safe-area-inset-bottom))] lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:pb-0">
                            <CartItems className="min-w-0" items={cart.items} />
                            <CartOrderSummary className="min-w-0" cart={cart} />
                        </div>
                    ) : (
                        <div className="flex flex-1 items-center justify-center py-8">
                            <CartItems items={cart.items} />
                        </div>
                    )}
                </div>
            </div>

            {hasItems ? (
                <div className="border-border bg-background/95 supports-[backdrop-filter]:bg-background/80 fixed inset-x-0 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-30 flex items-center gap-3 border-t px-3 py-2 backdrop-blur-xl lg:hidden">
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-[11px] leading-none font-medium">
                            Total
                        </p>
                        <p className="text-foreground text-base font-semibold">
                            {cart.formattedSubtotal}
                        </p>
                    </div>
                    <Button
                        asChild
                        className="h-11 min-w-0 flex-1"
                    >
                        <Link href={checkout.url()} prefetch>
                            Checkout
                        </Link>
                    </Button>
                </div>
            ) : null}
        </>
    );
}
