import { Link } from '@inertiajs/react';
import { Boxes } from 'lucide-react';
import { AccountListPagination } from '@/components/customer/account-list-pagination';
import type { CustomerWholesaleRequest } from '@/components/customer/wholesale-request-types';
import { WholesaleInquiryStatusBadge } from '@/components/orders/wholesale-inquiry-status-badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wholesale } from '@/routes';
import { show as wholesaleRequestShow } from '@/routes/customer/wholesale-requests';

type WholesaleRequestListProps = {
    requests: CustomerWholesaleRequest[];
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
};

export function WholesaleRequestList({
    requests,
    currentPage,
    lastPage,
    onPageChange,
    className,
}: WholesaleRequestListProps) {
    if (requests.length === 0) {
        return (
            <div
                className={cn(
                    'border-border bg-background flex flex-col items-center justify-center rounded-xl border px-6 py-16 text-center',
                    className,
                )}
            >
                <Boxes
                    className="text-muted-foreground mb-4 size-10"
                    strokeWidth={1.5}
                    aria-hidden
                />
                <h2 className="text-foreground text-lg font-semibold tracking-tight">
                    No wholesale requests yet
                </h2>
                <p className="text-muted-foreground mt-2 max-w-sm text-sm leading-relaxed">
                    Send a wholesale request and our team will review it and get
                    back to you with an order.
                </p>
                <Button asChild className="mt-6">
                    <Link href={wholesale.url()} prefetch>
                        Request wholesale pricing
                    </Link>
                </Button>
            </div>
        );
    }

    return (
        <div className={cn('flex flex-col gap-6', className)}>
            <ul className="flex flex-col gap-4">
                {requests.map((request) => (
                    <li key={request.id}>
                        <Link
                            href={wholesaleRequestShow.url(request.id)}
                            prefetch
                            aria-label={`View wholesale request ${request.id}`}
                            className="bg-accent flex flex-col gap-3 rounded-xl p-3 sm:p-5"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                <p className="text-muted-foreground min-w-0 text-xs font-medium">
                                    Request #{request.id} · {request.date}
                                </p>
                                <WholesaleInquiryStatusBadge
                                    status={request.status}
                                    label={request.statusLabel}
                                    className="rounded-full px-2.5 py-1 text-sm font-normal"
                                />
                            </div>
                            <div className="text-foreground flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                <span>
                                    {request.itemCount}{' '}
                                    {request.itemCount === 1
                                        ? 'product'
                                        : 'products'}
                                </span>
                                {request.orderNumber ? (
                                    <span className="font-medium">
                                        Order {request.orderNumber}
                                    </span>
                                ) : null}
                            </div>
                        </Link>
                    </li>
                ))}
            </ul>

            <AccountListPagination
                currentPage={currentPage}
                lastPage={lastPage}
                onPageChange={onPageChange}
            />
        </div>
    );
}
