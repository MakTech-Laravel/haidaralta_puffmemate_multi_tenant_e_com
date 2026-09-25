import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CartItems } from '@/components/storefront/cart/cart-items';
import { CartOrderSummary } from '@/components/storefront/cart/cart-order-summary';
import { shop } from '@/routes';
import type { StorefrontCart } from '@/types/storefront';

export default function StorefrontCart(cart: StorefrontCart) {
    return (
        <>
            <Head title="Cart" />

            <div className="bg-muted/50 flex-1">
                <div className="container mx-auto px-4 py-8 sm:px-6 sm:py-10">
                    <Link
                        href={shop.url()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm font-semibold transition"
                    >
                        <ArrowLeft className="size-4" />
                        Continue shopping
                    </Link>

                    <div className="mt-4 mb-8">
                        <div className="flex flex-wrap items-baseline gap-3">
                            <h1 className="text-foreground text-4xl font-extrabold tracking-tight">
                                Your cart
                            </h1>
                            <span className="text-muted-foreground text-lg font-medium">
                                {cart.itemCount}{' '}
                                {cart.itemCount === 1 ? 'item' : 'items'}
                            </span>
                        </div>
                    </div>

                    <div className="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                        <CartItems className="min-w-0" items={cart.items} />
                        <CartOrderSummary className="min-w-0" cart={cart} />
                    </div>
                </div>
            </div>
        </>
    );
}
