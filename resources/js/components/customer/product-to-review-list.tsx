import { ProductToReviewRow } from '@/components/customer/product-to-review-row';
import type { ProductToReview } from '@/components/customer/product-to-review-types';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { cn } from '@/lib/utils';

const PAGE_SIZE = 12;

type ProductToReviewListProps = {
    products: ProductToReview[];
    page: number;
    onPageChange: (page: number) => void;
    className?: string;
};

export function ProductToReviewList({
    products,
    page,
    onPageChange,
    className,
}: ProductToReviewListProps) {
    const totalPages = Math.max(1, Math.ceil(products.length / PAGE_SIZE));
    const currentPage = Math.min(Math.max(page, 1), totalPages);
    const pageProducts = products.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );
    const showPagination = products.length > PAGE_SIZE;

    return (
        <div className={cn('flex flex-col gap-6 border rounded-xs', className)}>
            <div>
                <div className="bg-muted text-muted-foreground hidden grid-cols-[minmax(0,1fr)_10rem_11rem] gap-6 px-4 py-3 text-xs font-semibold tracking-wide uppercase sm:grid">
                    <span className="text-xs font-medium">Products</span>
                    <span className="text-center text-xs font-medium">Shipping Status</span>
                    <span className="text-right text-xs font-medium">Action</span>
                </div>

                <ul className="px-2 sm:px-4">
                    {pageProducts.map((product) => (
                        <li key={product.id}>
                            <ProductToReviewRow product={product} />
                        </li>
                    ))}
                </ul>
            </div>

            {showPagination ? (
                <Pagination className="mb-2">
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href="#"
                                aria-disabled={currentPage <= 1}
                                className={cn(
                                    currentPage <= 1 &&
                                        'pointer-events-none opacity-50',
                                )}
                                onClick={(event) => {
                                    event.preventDefault();
                                    if (currentPage > 1) {
                                        onPageChange(currentPage - 1);
                                    }
                                }}
                            />
                        </PaginationItem>
                        {Array.from({ length: totalPages }, (_, index) => {
                            const pageNumber = index + 1;

                            return (
                                <PaginationItem key={pageNumber}>
                                    <PaginationLink
                                        href="#"
                                        isActive={pageNumber === currentPage}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            onPageChange(pageNumber);
                                        }}
                                    >
                                        {pageNumber}
                                    </PaginationLink>
                                </PaginationItem>
                            );
                        })}
                        <PaginationItem>
                            <PaginationNext
                                href="#"
                                aria-disabled={currentPage >= totalPages}
                                className={cn(
                                    currentPage >= totalPages &&
                                        'pointer-events-none opacity-50',
                                )}
                                onClick={(event) => {
                                    event.preventDefault();
                                    if (currentPage < totalPages) {
                                        onPageChange(currentPage + 1);
                                    }
                                }}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            ) : null}
        </div>
    );
}
