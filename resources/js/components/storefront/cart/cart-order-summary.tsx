import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { checkout } from '@/routes';
import type { StorefrontCart } from '@/types/storefront';

type CartOrderSummaryProps = {
    cart: StorefrontCart;
    className?: string;
};

export function CartOrderSummary({ cart, className }: CartOrderSummaryProps) {
    return (
        <aside
            className={cn(
                'border-border bg-background h-fit w-full max-w-full min-w-0 rounded-xl border p-5 lg:sticky lg:top-28',
                className,
            )}
        >
            <h2 className="text-foreground text-lg font-bold tracking-tight">
                Order summary
            </h2>

            <dl className="mt-5 space-y-3">
                <div className="flex min-w-0 items-center justify-between gap-3 text-sm">
                    <dt className="text-muted-foreground">Subtotal</dt>
                    <dd className="text-foreground shrink-0 font-semibold">
                        {cart.formattedSubtotal}
                    </dd>
                </div>
                <p className="text-muted-foreground text-xs">
                    Delivery is calculated at checkout.
                </p>
            </dl>

            <div className="border-border mt-5 flex min-w-0 items-end justify-between gap-3 border-t pt-4">
                <span className="text-foreground text-base font-bold">
                    Total
                </span>
                <span className="text-primary shrink-0 text-2xl font-bold">
                    {cart.formattedSubtotal}
                </span>
            </div>

            <Button
                asChild
                size="lg"
                className="mt-5 h-12 w-full rounded-none text-sm font-bold tracking-wide"
                disabled={cart.itemCount === 0}
            >
                <Link href={checkout.url()} prefetch>
                    Checkout →
                </Link>
            </Button>

            <p className="text-muted-foreground mt-3 flex items-center justify-center gap-1.5 text-xs">
                <Check className="text-tertiary size-3.5" aria-hidden />
                Secure checkout · No hidden fees
            </p>
        </aside>
    );
}
