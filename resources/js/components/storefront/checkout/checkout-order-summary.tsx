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

export type CheckoutShortfallLine = {
    productId: number;
    variantId: number | null;
    productPackageId?: number | null;
    productName: string;
    packageLabel?: string | null;
    quantity: number;
    inventoryQuantity?: number;
    localQuantity: number;
    shortfall: number;
    shortfallUnits?: number;
};

type CheckoutOrderSummaryProps = {
    items: StorefrontCartItem[];
    itemCount: number;
    subtotal: number;
    deliveryFee: number;
    deliveryLabel: string;
    deliveryIsEstimate?: boolean;
    couponsAllowed?: boolean;
    coupon: string;
    appliedCoupon: AppliedCoupon;
    couponError?: string;
    termsAccepted: boolean;
    termsError?: string;
    processing?: boolean;
    canPlaceOrder?: boolean;
    requiresLateDelivery?: boolean;
    shortfallLines?: CheckoutShortfallLine[];
    acceptLateDelivery?: boolean;
    acceptLateError?: string;
    /** Same day is selected while some items are on late delivery. */
    sameDayLateConflict?: boolean;
    /** Same day is selected but not possible (outside the radius, or no shifts left today). */
    sameDayUnavailable?: boolean;
    cartUrl?: string;
    onAcceptLateDeliveryChange?: (accepted: boolean) => void;
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
    couponsAllowed = true,
    coupon,
    appliedCoupon,
    couponError,
    termsAccepted,
    termsError,
    processing = false,
    canPlaceOrder = true,
    requiresLateDelivery = false,
    shortfallLines = [],
    acceptLateDelivery = false,
    acceptLateError,
    sameDayLateConflict = false,
    sameDayUnavailable = false,
    cartUrl = '/cart',
    onAcceptLateDeliveryChange,
    onCouponChange,
    onApplyCoupon,
    onRemoveCoupon,
    onTermsChange,
    className,
}: CheckoutOrderSummaryProps) {
    const brand = useAppBrand();
    const [couponOpen, setCouponOpen] = useState(Boolean(couponError));
    const discount = couponsAllowed ? (appliedCoupon?.discount ?? 0) : 0;

    useEffect(() => {
        if (couponError) {
            setCouponOpen(true);
        }
    }, [couponError]);
    const total = Math.max(subtotal - discount + deliveryFee, 0);

    return (
        <aside
            className={cn('bg-muted h-fit rounded-3xl p-6 lg:sticky lg:top-28', className)}
        >
            <div className="flex items-center justify-between gap-3">
                <h2 className="text-foreground text-2xl font-semibold tracking-tight">
                    Your order
                </h2>
                <span className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                    {itemCount} item{itemCount === 1 ? '' : 's'}
                </span>
            </div>

            <ul className="border-border mt-5 max-h-56 space-y-4 overflow-y-auto overscroll-contain border-y py-4 pr-1">
                {items.map((item) => (
                    <li key={item.id} className="flex items-center gap-3">
                        <div className="bg-card size-12 shrink-0 overflow-hidden rounded-xl">
                            <StorefrontProductImage
                                src={item.image}
                                alt={item.name}
                            />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-foreground truncate text-sm font-semibold">
                                {item.name}
                            </p>
                            {item.packageLabel ? (
                                <p className="text-muted-foreground text-xs">
                                    {item.packageLabel}
                                    {item.packageQuantity
                                        ? ` · ${item.packageQuantity} ${item.packageQuantity === 1 ? 'item' : 'items'}`
                                        : ''}
                                </p>
                            ) : null}
                            <p className="text-muted-foreground text-xs">
                                Quantity x {item.quantity}
                            </p>
                        </div>
                        <p className="text-foreground shrink-0 text-sm font-semibold">
                            {item.formattedLineTotal}
                        </p>
                    </li>
                ))}
            </ul>

            {couponsAllowed ? (
                <div className="mt-5 space-y-2">
                    {appliedCoupon ? (
                        <div className="bg-background flex items-center justify-between gap-3 rounded-xl p-3">
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
                                    onKeyDown={(event) => {
                                        if (event.key !== 'Enter') {
                                            return;
                                        }

                                        event.preventDefault();

                                        if (coupon.trim() !== '') {
                                            onApplyCoupon();
                                        }
                                    }}
                                    placeholder="Enter coupon code"
                                    autoComplete="off"
                                    className="h-11 rounded-xl"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={coupon.trim() === ''}
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
            ) : null}

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
                    <dd className="text-foreground font-semibold">
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
                <span className="text-foreground text-base font-semibold">
                    Total
                </span>
                <span className="text-foreground text-2xl font-semibold tracking-tight">
                    {formatStorefrontMoney(total)}
                </span>
            </div>

            {requiresLateDelivery && shortfallLines.length > 0 ? (
                <div className="border-border bg-background mt-5 space-y-3 rounded-2xl border p-4">
                    <p className="text-foreground text-sm font-semibold">
                        Some items may arrive later
                    </p>
                    <p className="text-muted-foreground text-xs leading-relaxed">
                        Your delivery branch does not have enough stock for
                        these items right now. Remove them from your cart, or
                        accept that they may be delivered later once stock is
                        collected.
                    </p>
                    <ul className="text-muted-foreground space-y-1 text-xs">
                        {shortfallLines.map((line) => {
                            const label = line.packageLabel
                                ? `${line.productName} · ${line.packageLabel}`
                                : line.productName;
                            const countNoun = line.packageLabel
                                ? 'packs'
                                : 'items';
                            const unitHint =
                                line.shortfallUnits !== undefined &&
                                line.packageLabel
                                    ? ` · ${line.shortfallUnits} units`
                                    : '';

                            return (
                                <li
                                    key={`${line.productId}:${line.variantId ?? 0}:${line.productPackageId ?? 0}`}
                                >
                                    {label}
                                    {line.shortfall < line.quantity
                                        ? ` (${line.shortfall} of ${line.quantity} ${countNoun} later${unitHint})`
                                        : ` (×${line.shortfall} ${countNoun}${unitHint})`}
                                </li>
                            );
                        })}
                    </ul>
                    <a
                        href={cartUrl}
                        className="text-foreground text-xs font-medium underline underline-offset-2"
                    >
                        Edit cart
                    </a>
                    <div className="flex items-start gap-2.5">
                        <Checkbox
                            id="checkout-accept-late"
                            checked={acceptLateDelivery}
                            onCheckedChange={(checked) =>
                                onAcceptLateDeliveryChange?.(checked === true)
                            }
                            className="mt-0.5"
                        />
                        <Label
                            htmlFor="checkout-accept-late"
                            className="text-foreground text-xs leading-relaxed font-normal"
                        >
                            I accept late delivery for these items
                        </Label>
                    </div>
                    {acceptLateError ? (
                        <p className="text-destructive text-xs">
                            {acceptLateError}
                        </p>
                    ) : null}
                </div>
            ) : null}

            <Button
                type="submit"
                size="lg"
                disabled={processing || !canPlaceOrder}
                title={
                    canPlaceOrder
                        ? undefined
                        : 'Fill every required field to place your order.'
                }
                className="mt-5 h-14 w-full text-base font-semibold"
            >
                {processing ? 'Placing order…' : 'Place order'}
            </Button>
            {!canPlaceOrder && !processing ? (
                <p className="text-muted-foreground mt-2 text-xs">
                    {requiresLateDelivery && !acceptLateDelivery
                        ? 'Accept late delivery for unavailable items, or remove them from your cart, then place your order.'
                        : sameDayUnavailable
                          ? 'Same day delivery is not available right now. Choose Late delivery, or enter a closer address if you are outside the same-day radius, then place your order.'
                          : sameDayLateConflict
                          ? 'Same day delivery is not available for late-delivery items. Choose Late delivery, or remove those items, then place your order.'
                          : 'Fill every required field, including an Express time slot when Express is selected, then place your order.'}
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
