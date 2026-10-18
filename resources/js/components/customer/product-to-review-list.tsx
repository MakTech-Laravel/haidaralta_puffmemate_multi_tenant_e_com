import { AccountListPagination } from '@/components/customer/account-list-pagination';
import { ProductToReviewRow } from '@/components/customer/product-to-review-row';
import type { ProductToReview } from '@/components/customer/product-to-review-types';
import { cn } from '@/lib/utils';

type ProductToReviewListProps = {
    products: ProductToReview[];
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
};

export function ProductToReviewList({
    products,
    currentPage,
    lastPage,
    onPageChange,
    className,
}: ProductToReviewListProps) {
    return (
        <div className={cn('flex flex-col gap-6 rounded-xs border', className)}>
            <div>
                <div className="bg-muted text-muted-foreground hidden grid-cols-[minmax(0,1fr)_8rem_10rem_11rem] gap-6 px-4 py-3 text-xs font-semibold tracking-wide uppercase sm:grid">
                    <span className="text-xs font-medium">Products</span>
                    <span className="text-center text-xs font-medium">
                        Order ID
                    </span>
                    <span className="text-center text-xs font-medium">
                        Shipping Status
                    </span>
                    <span className="text-right text-xs font-medium">
                        Action
                    </span>
                </div>

                <ul className="px-2 sm:px-4">
                    {products.map((product) => (
                        <li key={product.id}>
                            <ProductToReviewRow product={product} />
                        </li>
                    ))}
                </ul>
            </div>

            <AccountListPagination
                className="mb-2"
                currentPage={currentPage}
                lastPage={lastPage}
                onPageChange={onPageChange}
            />
        </div>
    );
}
