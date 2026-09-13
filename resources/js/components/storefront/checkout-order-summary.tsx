import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatMoney,
    type CheckoutItem,
    type CheckoutSummary,
} from '@/lib/storefront-checkout';
import { cn } from '@/lib/utils';

type CheckoutOrderSummaryProps = {
    items: CheckoutItem[];
    itemCount: number;
    summary: CheckoutSummary;
    trustBadges: string[];
    legalNote: string;
    coupon: string;
    termsAccepted: boolean;
    onCouponChange: (value: string) => void;
    onTermsChange: (accepted: boolean) => void;
    className?: string;
};

export function CheckoutOrderSummary({
    items,
    itemCount,
    summary,
    trustBadges,
    legalNote,
    coupon,
    termsAccepted,
    onCouponChange,
    onTermsChange,
    className,
}: CheckoutOrderSummaryProps) {
    return (
        <aside
            className={cn(
                'bg-accent h-fit p-5 lg:sticky lg:top-28',
                className,
            )}
        >
            <div className="flex items-center justify-between gap-3">
                <h2 className="text-foreground text-2xl font-bold tracking-tight">
                    Your order
                </h2>
                <span className="text-muted-foreground text-xs font-extrabold tracking-wider uppercase">
                    {itemCount} item{itemCount === 1 ? '' : 's'}
                </span>
            </div>

            <ul className="mt-5 space-y-4">
                {items.map((item) => (
                    <li key={item.id} className="flex items-center gap-3">
                        <div className="bg-muted relative size-12 shrink-0 overflow-hidden rounded-md">
                            <div
                                className="absolute inset-0 bg-cover bg-center"
                                style={{
                                    backgroundImage: `url(${item.image})`,
                                }}
                                role="img"
                                aria-label={item.name}
                            />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-foreground truncate text-sm font-semibold">
                                {item.name}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                Quantity x {item.quantity}
                            </p>
                        </div>
                        <p className="text-foreground shrink-0 text-sm font-bold">
                            {formatMoney(item.unitPrice * item.quantity)}
                        </p>
                    </li>
                ))}
            </ul>

            <div className="mt-5 flex gap-2">
                <Input
                    value={coupon}
                    onChange={(event) => onCouponChange(event.target.value)}
                    placeholder="Coupon code"
                    className="h-10 bg-background rounded-none border-none"
                    aria-label="Coupon code"
                />
                <Button
                    type="button"
                    className="bg-foreground text-background hover:bg-foreground/90 h-10 shrink-0 rounded-none px-4 font-bold"
                >
                    Apply
                </Button>
            </div>

            <dl className="mt-5 space-y-2.5">
                <div className="flex items-center justify-between gap-3 text-sm">
                    <dt className="text-muted-foreground">Subtotal</dt>
                    <dd className="text-foreground font-semibold">
                        {formatMoney(summary.subtotal)}
                    </dd>
                </div>
                <div className="flex items-center justify-between gap-3 text-sm">
                    <dt className="text-muted-foreground">Delivery</dt>
                    <dd className="text-foreground font-bold">
                        {summary.deliveryAmount === null
                            ? summary.deliveryLabel
                            : formatMoney(summary.deliveryAmount)}
                    </dd>
                </div>
            </dl>

            <div className="border-border mt-4 flex items-end justify-between gap-3 border-t pt-4">
                <span className="text-foreground text-base font-bold">Total</span>
                <span className="text-foreground text-2xl font-bold">
                    {formatMoney(summary.total)}
                </span>
            </div>

            <Button
                type="button"
                size="lg"
                className="mt-5 h-12 w-full rounded-none text-sm font-bold tracking-wide uppercase"
            >
                Place order
            </Button>

            <div className="mt-4 flex items-start gap-2.5">
                <Checkbox
                    id="checkout-terms"
                    checked={termsAccepted}
                    onCheckedChange={(checked) =>
                        onTermsChange(checked === true)
                    }
                    className="mt-0.5"
                />
                <Label
                    htmlFor="checkout-terms"
                    className="text-muted-foreground text-xs leading-relaxed font-normal"
                >
                    By placing your order, you confirm your details are correct
                    and agree to PuffMeMate&apos;s terms.
                </Label>
            </div>

            <div className="mt-5 grid grid-cols-2 gap-2">
                {trustBadges.map((badge) => (
                    <div
                        key={badge}
                        className="border-border text-muted-foreground rounded-md border px-2.5 py-2 text-center text-[0.7rem] font-medium"
                    >
                        {badge}
                    </div>
                ))}
            </div>

            <p className="border-primary/20 text-primary mt-5 rounded-md border px-3 py-2.5 text-[0.7rem] leading-relaxed">
                {legalNote}
            </p>
        </aside>
    );
}
