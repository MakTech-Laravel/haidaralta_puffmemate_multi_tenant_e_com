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

export function CartOrderSummary({
    summary,
    className,
}: CartOrderSummaryProps) {
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

            <div className="mt-5 space-y-5">
                {summary.branches.map((branch) => (
                    <div key={branch.branchName} className="min-w-0">
                        <p className="text-foreground text-sm font-bold break-words">
                            {branch.branchName}
                        </p>
                        <dl className="mt-3 space-y-3">
                            {branch.lines.map((line) => {
                                const isDiscount = line.tone === 'discount';

                                return (
                                    <div
                                        key={`${branch.branchName}-${line.label}-${line.amount}`}
                                        className="flex min-w-0 items-center justify-between gap-3 text-sm"
                                    >
                                        <dt
                                            className={cn(
                                                'inline-flex min-w-0 flex-1 items-center gap-1.5',
                                                isDiscount
                                                    ? 'text-tertiary font-medium'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {isDiscount ? (
                                                <Tag
                                                    className="size-3.5 shrink-0"
                                                    aria-hidden
                                                />
                                            ) : null}
                                            <span className="min-w-0 break-words">
                                                {line.label}
                                            </span>
                                        </dt>
                                        <dd
                                            className={cn(
                                                'shrink-0 font-semibold',
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
                    </div>
                ))}
            </div>

            <div className="border-border mt-5 flex min-w-0 items-end justify-between gap-3 border-t pt-4">
                <span className="text-foreground text-base font-bold">
                    Total
                </span>
                <span className="text-primary shrink-0 text-2xl font-bold">
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
            <p className="text-muted-foreground mt-1 text-center text-xs">
                Each branch will be an individual order
            </p>
        </aside>
    );
}
