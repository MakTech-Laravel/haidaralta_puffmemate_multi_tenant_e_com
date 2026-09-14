import { Link } from '@inertiajs/react';
import { Check, Tag } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatMoney, type CartSummary } from '@/lib/storefront-cart';
import { cn } from '@/lib/utils';
import { checkout } from '@/routes';

type CartOrderSummaryProps = {
    summary: CartSummary;
    className?: string;
};

export function CartOrderSummary({ summary, className }: CartOrderSummaryProps) {
    return (
        <aside
            className={cn(
                'border-border bg-background h-fit rounded-xl border p-5 lg:sticky lg:top-28',
                className,
            )}
        >
            <h2 className="text-foreground text-lg font-bold tracking-tight">
                Order summary
            </h2>

            <dl className="mt-5 space-y-3">
                {summary.lines.map((line) => {
                    const isDiscount = line.tone === 'discount';

                    return (
                        <div
                            key={`${line.label}-${line.amount}`}
                            className="flex items-center justify-between gap-3 text-sm"
                        >
                            <dt
                                className={cn(
                                    'inline-flex items-center gap-1.5',
                                    isDiscount
                                        ? 'text-tertiary font-medium'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {isDiscount ? (
                                    <Tag className="size-3.5" aria-hidden />
                                ) : null}
                                {line.label}
                            </dt>
                            <dd
                                className={cn(
                                    'font-semibold',
                                    isDiscount
                                        ? 'text-tertiary'
                                        : 'text-foreground',
                                )}
                            >
                                {formatMoney(line.amount)}
                            </dd>
                        </div>
                    );
                })}
            </dl>

            <div className="border-border mt-5 flex items-end justify-between gap-3 border-t pt-4">
                <span className="text-foreground text-base font-bold">Total</span>
                <span className="text-primary text-2xl font-bold">
                    {formatMoney(summary.total)}
                </span>
            </div>

            <Button
                asChild
                size="lg"
                className="mt-5 h-12 w-full rounded-none text-sm font-bold tracking-wide"
            >
                <Link href={checkout()} prefetch>
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
