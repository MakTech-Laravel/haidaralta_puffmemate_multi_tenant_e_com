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

            <div className="bg-muted flex-1">
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
                            <h1 className="text-foreground text-2xl font-semibold tracking-tight sm:text-4xl">
                                Your cart
                            </h1>
                            <span className="text-muted-foreground text-lg font-medium">
                                {cart.itemCount}{' '}
                                {cart.itemCount === 1 ? 'item' : 'items'}
                            </span>
                        </div>
                    </div>

                    <div className="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                        <CartOrderSummary
                            className="order-first min-w-0 lg:order-none"
                            cart={cart}
                        />
                        <CartItems className="min-w-0" items={cart.items} />
                    </div>
                </div>
            </div>
        </>
    );
}
