import { Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Review = {
    id: string;
    author: string;
    date: string;
    rating: number;
    body: string;
};

type ProductReviewsProps = {
    rating: number;
    reviewCount: number;
    reviews: Review[];
};

export function ProductReviews({
    rating,
    reviewCount,
    reviews,
}: ProductReviewsProps) {
    return (
        <section className="flex flex-col gap-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-foreground text-xl font-bold tracking-tight uppercase">
                    Reviews ({reviewCount})
                </h2>
                <Button
                    type="button"
                    variant="outline"
                    className="border-primary text-primary hover:bg-primary hover:text-primary-foreground rounded-none"
                >
                    Write a Review
                </Button>
            </div>

            <p className="text-foreground text-base font-semibold">
                {rating} out of 5
            </p>

            <ul className="flex flex-col gap-4">
                {reviews.map((review) => (
                    <li
                        key={review.id}
                        className="border-border rounded-xl border p-4"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-foreground text-sm font-semibold">
                                {review.author}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {review.date}
                            </p>
                        </div>
                        <div className="mt-2 flex items-center gap-0.5">
                            {Array.from({ length: 5 }).map((_, index) => (
                                <Star
                                    key={index}
                                    className={cn(
                                        'size-3.5',
                                        index < review.rating
                                            ? 'fill-primary text-primary'
                                            : 'text-muted-foreground',
                                    )}
                                />
                            ))}
                        </div>
                        <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                            {review.body}
                        </p>
                    </li>
                ))}
            </ul>
        </section>
    );
}
