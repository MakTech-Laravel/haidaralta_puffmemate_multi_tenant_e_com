import type { OrderTrackProduct } from '@/components/customer/order-track-types';
import { StorefrontProductImage } from '@/components/storefront/storefront-product-image';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type OrderTrackProductsProps = {
    products: OrderTrackProduct[];
    subtotalLabel: string;
    discountLabel?: string | null;
    couponCode?: string | null;
    deliveryFeeLabel: string;
    totalLabel: string;
};

export function OrderTrackProducts({
    products,
    subtotalLabel,
    discountLabel,
    couponCode,
    deliveryFeeLabel,
    totalLabel,
}: OrderTrackProductsProps) {
    return (
        <Card className="bg-background gap-0 rounded-none border-none py-5 shadow-none">
            <CardHeader className="flex flex-row items-center justify-between gap-3 px-5 pb-0">
                <CardTitle className="text-foreground text-lg font-semibold sm:text-xl">
                    Items
                </CardTitle>
                <span className="text-muted-foreground text-xs font-extrabold tracking-wider uppercase">
                    {products.length} item{products.length === 1 ? '' : 's'}
                </span>
            </CardHeader>
            <CardContent className="px-5">
                <ul className="border-border mt-5 max-h-72 space-y-4 overflow-y-auto overscroll-contain border-y py-4 pr-1">
                    {products.map((product, index) => (
                        <li
                            key={`${product.name}-${index}`}
                            className="flex items-center gap-3"
                        >
                            <div className="bg-muted size-14 shrink-0 overflow-hidden rounded-md">
                                <StorefrontProductImage
                                    src={product.image}
                                    alt={product.name}
                                />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p
                                    className={
                                        product.isRemoved
                                            ? 'text-muted-foreground truncate text-sm font-semibold line-through'
                                            : 'text-foreground truncate text-sm font-semibold'
                                    }
                                >
                                    {product.name}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {product.quantityLabel}
                                    {product.isLateDelivery
                                        ? ' · Late delivery'
                                        : ''}
                                    {product.isRemoved ? ' · Removed' : ''}
                                </p>
                            </div>
                            <p
                                className={
                                    product.isRemoved
                                        ? 'text-muted-foreground shrink-0 text-sm font-bold line-through'
                                        : 'text-foreground shrink-0 text-sm font-bold'
                                }
                            >
                                {product.isRemoved
                                    ? 'Removed'
                                    : product.priceLabel}
                            </p>
                        </li>
                    ))}
                </ul>

                <dl className="mt-5 space-y-2.5">
                    <div className="flex items-center justify-between gap-3 text-sm">
                        <dt className="text-muted-foreground">Subtotal</dt>
                        <dd className="text-foreground font-semibold">
                            {subtotalLabel}
                        </dd>
                    </div>
                    {discountLabel ? (
                        <div className="flex items-center justify-between gap-3 text-sm">
                            <dt className="text-muted-foreground">
                                {couponCode ?? 'Discount'}
                            </dt>
                            <dd className="text-foreground font-semibold">
                                {discountLabel}
                            </dd>
                        </div>
                    ) : null}
                    <div className="flex items-center justify-between gap-3 text-sm">
                        <dt className="text-muted-foreground">Delivery</dt>
                        <dd className="text-foreground font-bold">
                            {deliveryFeeLabel}
                        </dd>
                    </div>
                </dl>

                <div className="border-border mt-4 flex items-end justify-between gap-3 border-t pt-4">
                    <span className="text-foreground text-base font-bold">
                        Total
                    </span>
                    <span className="text-foreground text-2xl font-bold tracking-tight">
                        {totalLabel}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}
