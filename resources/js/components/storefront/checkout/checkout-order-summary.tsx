import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
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
    deliveryIsEstimate?: boolean;
    coupon: string;
    appliedCoupon: AppliedCoupon;
    couponError?: string;
    termsAccepted: boolean;
    termsError?: string;
    processing?: boolean;
    canPlaceOrder?: boolean;
    onCouponChange: (value: string) => void;
    onApplyCoupon: () => void;
    onRemoveCoupon: () => void;
    onTermsChange: (accepted: boolean) => void;
    className?: string;
};

export function CheckoutOrderSummary({
    items,
    itemCount,
    subtotal,
    deliveryFee,
    deliveryLabel,
    deliveryIsEstimate = false,
    coupon,
    appliedCoupon,
    couponError,
    termsAccepted,
    termsError,
    processing = false,
    canPlaceOrder = true,
    onCouponChange,
    onApplyCoupon,
    onRemoveCoupon,
    onTermsChange,
    className,
}: CheckoutOrderSummaryProps) {
    const brand = useAppBrand();
    const [couponOpen, setCouponOpen] = useState(Boolean(couponError));
    const discount = appliedCoupon?.discount ?? 0;

    useEffect(() => {
        if (couponError) {
            setCouponOpen(true);
        }
    }, [couponError]);
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

            <ul className="border-border mt-5 max-h-64 space-y-4 overflow-y-auto overscroll-contain border-y py-4 pr-1">
                {items.map((item) => (
                    <li key={item.id} className="flex items-center gap-3">
                        <div className="bg-muted size-12 shrink-0 overflow-hidden rounded-md">
                            <StorefrontProductImage
                                src={item.image}
                                alt={item.name}
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
                {appliedCoupon ? (
                    <div className="bg-background flex items-center justify-between gap-3 p-3">
                        <div className="min-w-0">
                            <p className="text-foreground text-sm font-semibold">
                                {appliedCoupon.code}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {appliedCoupon.formattedDiscount} off
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                setCouponOpen(false);
                                onRemoveCoupon();
                            }}
                        >
                            Remove
                        </Button>
                    </div>
                ) : couponOpen ? (
                    <div className="space-y-2">
                        <Label htmlFor="checkout-coupon">Coupon code</Label>
                        <div className="flex gap-2">
                            <Input
                                id="checkout-coupon"
                                value={coupon}
                                onChange={(event) =>
                                    onCouponChange(event.target.value)
                                }
                                placeholder="Enter coupon code"
                                autoComplete="off"
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
                        <button
                            type="button"
                            className="text-muted-foreground text-xs underline underline-offset-2"
                            onClick={() => {
                                setCouponOpen(false);
                                onCouponChange('');
                            }}
                        >
                            I don’t have a coupon
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        className="text-foreground text-sm font-medium underline underline-offset-2"
                        onClick={() => setCouponOpen(true)}
                    >
                        Have a coupon code?
                    </button>
                )}
                {couponError ? (
                    <p className="text-destructive text-xs">{couponError}</p>
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
                {deliveryIsEstimate ? (
                    <p className="text-muted-foreground text-xs">
                        Final delivery fee depends on distance to the branch
                        fulfilling your order and is shown on your
                        confirmation.
                    </p>
                ) : null}
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
                disabled={processing || !canPlaceOrder}
                title={
                    canPlaceOrder
                        ? undefined
                        : 'Fill every required field to place your order.'
                }
                className="mt-5 h-12 w-full rounded-none text-sm font-bold tracking-wide uppercase"
            >
                {processing ? 'Placing order…' : 'Place order'}
            </Button>
            {!canPlaceOrder && !processing ? (
                <p className="text-muted-foreground mt-2 text-xs">
                    Fill every required field, including a delivery date for
                    Express delivery, then place your order.
                </p>
            ) : null}

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
            {termsError ? (
                <p className="text-destructive mt-2 text-xs">{termsError}</p>
            ) : null}
        </aside>
    );
}
