import { Head, Link } from '@inertiajs/react';
import type { CustomerWholesaleRequestDetail } from '@/components/customer/wholesale-request-types';
import { WholesaleInquiryStatusBadge } from '@/components/orders/wholesale-inquiry-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { show as orderShow } from '@/routes/customer/orders';
import { index as wholesaleRequestsIndex } from '@/routes/customer/wholesale-requests';

type CustomerWholesaleRequestShowProps = {
    request: CustomerWholesaleRequestDetail;
};

export default function CustomerWholesaleRequestShow({
    request,
}: CustomerWholesaleRequestShowProps) {
    const contactLines = [
        request.company,
        request.address,
        request.phone,
        request.email,
    ].filter((line): line is string => Boolean(line));

    return (
        <>
            <Head title={`Wholesale request #${request.id}`} />

            <div className="space-y-5">
                <div className="bg-accent space-y-4 p-4 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <h2 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                                Wholesale request #{request.id}
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Submitted on {request.date}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center justify-end gap-2">
                            <WholesaleInquiryStatusBadge
                                status={request.status}
                                label={request.statusLabel}
                                className="rounded-sm px-2.5 py-0.5 text-sm font-medium"
                            />
                            {request.orderNumber ? (
                                <Button size="sm" asChild>
                                    <Link
                                        href={orderShow.url(
                                            request.orderNumber,
                                        )}
                                        prefetch
                                    >
                                        Track order {request.orderNumber}
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    </div>

                    {request.rejectionReason ? (
                        <p className="bg-destructive/10 text-foreground rounded-md p-3 text-sm whitespace-pre-wrap">
                            {request.rejectionReason}
                        </p>
                    ) : null}
                </div>

                <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <Card className="bg-background gap-3 rounded-none border-none py-5 shadow-none">
                        <CardHeader className="px-5 pb-0">
                            <CardTitle className="text-foreground text-lg font-semibold sm:text-xl">
                                Requested products
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-5 text-sm">
                            {request.items.length > 0 ? (
                                <ul className="divide-border divide-y">
                                    {request.items.map((item) => (
                                        <li
                                            key={`${item.name}-${item.sku ?? ''}`}
                                            className="flex items-center justify-between gap-3 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="text-foreground truncate font-medium">
                                                    {item.name}
                                                </p>
                                                {item.sku ? (
                                                    <p className="text-muted-foreground text-xs">
                                                        SKU {item.sku}
                                                    </p>
                                                ) : null}
                                            </div>
                                            <span className="text-foreground shrink-0 font-semibold tabular-nums">
                                                × {item.quantity}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground">
                                    No specific products were selected.
                                </p>
                            )}
                            {request.message ? (
                                <p className="text-muted-foreground border-border mt-4 border-t pt-4 leading-relaxed whitespace-pre-wrap">
                                    {request.message}
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card className="bg-background gap-3 rounded-none border-none py-5 shadow-none">
                        <CardHeader className="px-5 pb-0">
                            <CardTitle className="text-foreground text-lg font-semibold sm:text-xl">
                                Contact details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 px-5 text-sm leading-relaxed">
                            {contactLines.map((line) => (
                                <p key={line} className="text-muted-foreground">
                                    {line}
                                </p>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

CustomerWholesaleRequestShow.layout = (
    props: CustomerWholesaleRequestShowProps,
) => ({
    breadcrumbs: [
        {
            title: 'Wholesale requests',
            href: wholesaleRequestsIndex.url(),
        },
        {
            title: `#${props.request.id}`,
            href: '#',
        },
    ],
});
