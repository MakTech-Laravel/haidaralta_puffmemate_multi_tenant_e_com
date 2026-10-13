import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { cn } from '@/lib/utils';

type AccountListPaginationProps = {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
};

export function AccountListPagination({
    currentPage,
    lastPage,
    onPageChange,
    className,
}: AccountListPaginationProps) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <Pagination className={className}>
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
                {Array.from({ length: lastPage }, (_, index) => {
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
                        aria-disabled={currentPage >= lastPage}
                        className={cn(
                            currentPage >= lastPage &&
                                'pointer-events-none opacity-50',
                        )}
                        onClick={(event) => {
                            event.preventDefault();
                            if (currentPage < lastPage) {
                                onPageChange(currentPage + 1);
                            }
                        }}
                    />
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}
