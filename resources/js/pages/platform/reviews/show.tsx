import { Head, Link, router } from '@inertiajs/react';
import { Eye, EyeOff, Star } from 'lucide-react';
import type { ReactNode } from 'react';
import ProductReviewController from '@/actions/App/Http/Controllers/Platform/ProductReviewController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Message, MessageContent } from '@/components/ui/message';
import { formatAppDateTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type ReviewDetail = {
    id: number;
    rating: number;
    body: string;
    hidden: boolean;
    created_at: string | null;
    customer: {
        id: number | null;
        name: string | null;
        phone: string | null;
        email: string | null;
    };
    product: {
        id: number | null;
        name: string | null;
        sku: string | null;
        slug: string | null;
        url: string | null;
    };
    order: {
        id: number | null;
        order_number: string | null;
        url: string | null;
    };
    branch: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

function Fact({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </dt>
            <dd className="text-sm font-medium wrap-break-word">{value}</dd>
        </div>
    );
}

function displayValue(value: string | null | undefined): string {
    return value && value !== '' ? value : '—';
}

function RatingStars({ rating }: { rating: number }) {
    const safeRating = Math.min(5, Math.max(0, rating));

    return (
        <div className="flex items-center gap-2">
            <div className="flex items-center">
                {Array.from({ length: 5 }).map((_, index) => (
                    <Star
                        key={index}
                        className={cn(
                            'size-5',
                            index < safeRating
                                ? 'fill-review-star text-review-star'
                                : 'text-muted-foreground',
                        )}
                    />
                ))}
            </div>
            <span className="text-sm font-semibold">
                {safeRating} / 5
            </span>
        </div>
    );
}

function LinkedValue({
    href,
    children,
}: {
    href: string | null;
    children: string | null;
}) {
    if (!children) {
        return '—';
    }

    if (!href) {
        return children;
    }

    return (
        <Link href={href} className="text-foreground hover:underline">
            {children}
        </Link>
    );
}

export default function PlatformReviewsShow({
    review,
}: {
    review: ReviewDetail;
}) {
    const customerName = review.customer.name ?? 'Customer';

    return (
        <>
            <Head title={`Review #${review.id}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={`Review #${review.id}`}
                            description={customerName}
                        />
                        {review.hidden ? (
                            <Badge variant="outline">Hidden</Badge>
                        ) : (
                            <Badge variant="secondary">Visible</Badge>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {review.hidden ? (
                            <Button
                                type="button"
                                onClick={() =>
                                    router.post(
                                        ProductReviewController.unhide.url(
                                            review.id,
                                        ),
                                    )
                                }
                            >
                                <Eye className="size-4" />
                                Unhide
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() =>
                                    router.post(
                                        ProductReviewController.hide.url(
                                            review.id,
                                        ),
                                    )
                                }
                            >
                                <EyeOff className="size-4" />
                                Hide
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={ProductReviewController.index.url()}>
                                Back
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card className="shadow-sm">
                    <CardContent className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Fact
                            label="Rating"
                            value={<RatingStars rating={review.rating} />}
                        />
                        <Fact
                            label="Created at"
                            value={
                                formatAppDateTime(review.created_at) || '—'
                            }
                        />
                        <Fact
                            label="Order ID"
                            value={
                                <LinkedValue href={review.order.url}>
                                    {review.order.order_number}
                                </LinkedValue>
                            }
                        />
                        <Fact
                            label="Branch"
                            value={displayValue(review.branch?.name)}
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Customer</CardTitle>
                            <CardDescription>
                                Who wrote this review.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                <Fact
                                    label="Name"
                                    value={displayValue(review.customer.name)}
                                />
                                <Fact
                                    label="Phone"
                                    value={displayValue(review.customer.phone)}
                                />
                                <Fact
                                    label="Email"
                                    value={displayValue(review.customer.email)}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Product</CardTitle>
                            <CardDescription>
                                Product this review is about.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                <Fact
                                    label="Name"
                                    value={
                                        <LinkedValue href={review.product.url}>
                                            {review.product.name}
                                        </LinkedValue>
                                    }
                                />
                                <Fact
                                    label="SKU"
                                    value={displayValue(review.product.sku)}
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Review</CardTitle>
                        <CardDescription>
                            Full customer feedback.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-primary/5 ring-primary/15 rounded-xl p-4 ring-1">
                            <Message align="start">
                                <MessageContent>
                                    <Bubble
                                        variant="tinted"
                                        className="max-w-full"
                                    >
                                        <BubbleContent className="max-w-prose sm:max-w-full">
                                            <p className="wrap-break-word whitespace-pre-wrap">
                                                {displayValue(review.body)}
                                            </p>
                                        </BubbleContent>
                                    </Bubble>
                                </MessageContent>
                            </Message>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PlatformReviewsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Reviews',
            href: '/platform/reviews',
        },
        { title: 'Detail', href: '#' },
    ],
};
