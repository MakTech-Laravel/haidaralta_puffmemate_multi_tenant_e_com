import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';
import type { StorefrontCartItem } from '@/types/storefront';
import { formatStorefrontMoney } from '@/types/storefront';

type AppliedCoupon = {
    code: string;
    discount: number;
    formattedDiscount: string;
} | null;

type CheckoutOrderSummaryProps = {
    items: StorefrontCartItem[];
    itemCount: number;
    subtotal: number;
    deliveryFee: number;
    deliveryLabel: string;
    coupon: string;
    appliedCoupon: AppliedCoupon;
    couponError?: string;
    termsAccepted: boolean;
    processing?: boolean;
    onCouponChange: (value: string) => void;
    onApplyCoupon: () => void;
    onTermsChange: (accepted: boolean) => void;
    className?: string;
};

export function CheckoutOrderSummary({
    items,
    itemCount,
    subtotal,
    deliveryFee,
    deliveryLabel,
    coupon,
    appliedCoupon,
    couponError,
    termsAccepted,
    processing = false,
    onCouponChange,
    onApplyCoupon,
    onTermsChange,
    className,
}: CheckoutOrderSummaryProps) {
    const brand = useAppBrand();
    const discount = appliedCoupon?.discount ?? 0;
    const total = Math.max(subtotal - discount + deliveryFee, 0);

    return (
        <aside
            className={cn('bg-accent h-fit p-5 lg:sticky lg:top-28', className)}
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
                        <div className="bg-muted size-12 shrink-0 overflow-hidden rounded-md">
                            <img
                                src={item.image}
                                alt={item.name}
                                className="size-full object-cover"
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
                            {item.formattedLineTotal}
                        </p>
                    </li>
                ))}
            </ul>

            <div className="mt-5 space-y-2">
                <Label htmlFor="checkout-coupon">Coupon</Label>
                <div className="flex gap-2">
                    <Input
                        id="checkout-coupon"
                        value={coupon}
                        onChange={(event) => onCouponChange(event.target.value)}
                        className="bg-background h-11 rounded-none border-none"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onApplyCoupon}
                    >
                        Apply
                    </Button>
                </div>
                {couponError ? (
                    <p className="text-destructive text-xs">{couponError}</p>
                ) : null}
                {appliedCoupon ? (
                    <p className="text-sm font-medium">
                        {appliedCoupon.code} · {appliedCoupon.formattedDiscount}
                    </p>
                ) : null}
            </div>

            <dl className="mt-5 space-y-2.5">
                <div className="flex items-center justify-between gap-3 text-sm">
                    <dt className="text-muted-foreground">Subtotal</dt>
                    <dd className="text-foreground font-semibold">
                        {formatStorefrontMoney(subtotal)}
                    </dd>
                </div>
                {discount > 0 ? (
                    <div className="flex items-center justify-between gap-3 text-sm">
                        <dt className="text-muted-foreground">Discount</dt>
                        <dd className="text-foreground font-semibold">
                            -{formatStorefrontMoney(discount)}
                        </dd>
                    </div>
                ) : null}
                <div className="flex items-center justify-between gap-3 text-sm">
                    <dt className="text-muted-foreground">Delivery</dt>
                    <dd className="text-foreground font-bold">
                        {deliveryLabel}
                    </dd>
                </div>
            </dl>

            <div className="border-border mt-4 flex items-end justify-between gap-3 border-t pt-4">
                <span className="text-foreground text-base font-bold">
                    Total
                </span>
                <span className="text-foreground text-2xl font-bold">
                    {formatStorefrontMoney(total)}
                </span>
            </div>

            <Button
                type="submit"
                size="lg"
                disabled={processing}
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
                    and agree to{' '}
                    <a
                        href="/terms"
                        className="text-foreground underline underline-offset-2"
                        target="_blank"
                        rel="noreferrer"
                    >
                        {brand.name}&apos;s terms
                    </a>
                    .
                </Label>
            </div>
        </aside>
    );
}
