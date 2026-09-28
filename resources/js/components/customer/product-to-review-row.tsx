import { Link } from '@inertiajs/react';
import type { ProductToReview } from '@/components/customer/product-to-review-types';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as productToReviewShow } from '@/routes/customer/product-to-review';

const statusLabel: Record<ProductToReview['shippingStatus'], string> = {
    delivered: 'Delivered',
};

const listGridClass =
    'sm:grid-cols-[minmax(0,1fr)_8rem_10rem_11rem]';

type ProductToReviewRowProps = {
    product: ProductToReview;
    className?: string;
};

export function ProductToReviewRow({
    product,
    className,
}: ProductToReviewRowProps) {
    const actionHref = product.reviewed
        ? product.viewUrl
        : productToReviewShow.url(product.id);

    return (
        <div
            className={cn(
                'border-border grid grid-cols-1 items-center gap-4 border-b py-4 last:border-b-0 sm:gap-6',
                listGridClass,
                className,
            )}
        >
            <div className="flex min-w-0 items-center gap-3">
                <div className="bg-muted size-16 shrink-0 overflow-hidden rounded-sm">
                    <img
                        src={product.image}
                        alt=""
                        className="size-full object-cover"
                    />
                </div>
                <p className="text-foreground truncate text-sm font-normal">
                    {product.name}
                </p>
            </div>

            <div className="flex flex-col gap-3 sm:contents">
                <p className="text-foreground text-sm font-semibold sm:text-center">
                    <span className="text-muted-foreground mr-2 font-semibold sm:hidden">
                        Order ID
                    </span>
                    {product.orderId}
                </p>

                <p className="text-foreground text-sm font-semibold sm:text-center">
                    <span className="text-muted-foreground mr-2 font-semibold sm:hidden">
                        Status
                    </span>
                    {statusLabel[product.shippingStatus]}
                </p>

                <div className="w-full min-w-0 sm:w-auto sm:justify-self-end">
                    {actionHref ? (
                        <Button
                            type="button"
                            size="sm"
                            className="h-9 w-full rounded-md px-4 text-xs font-semibold tracking-wide uppercase sm:w-auto"
                            asChild
                        >
                            <Link href={actionHref}>
                                {product.reviewed
                                    ? 'View Review'
                                    : 'Write a Review'}
                            </Link>
                        </Button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
