import { Star } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

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
        <section className="bg-accent flex flex-col gap-6 p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="text-foreground text-2xl font-semibold uppercase font-inter">
                        Reviews ({reviewCount})
                    </h2>
                    <p className="text-foreground text-base font-normal font-inter">
                        Get specific details about this product from customers
                        who own it.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    className="border-primary text-primary hover:bg-primary hover:text-primary-foreground rounded-none"
                >
                    Write a Review
                </Button>
            </div>

            <div>
                <div className="flex items-center gap-2">
                    <div className="flex items-center">
                        {Array.from({ length: 5 }).map((_, index) => (
                            <Star
                                key={index}
                                className={cn(
                                    "size-7",
                                    index < rating
                                        ? "fill-review-star text-review-star"
                                        : "text-muted-foreground",
                                )}
                            />
                        ))}
                    </div>

                    <p className="text-foreground text-lg font-bold font-inter">
                        {rating} out of 5
                    </p>
                </div>
            </div>

            <ul className="flex flex-col gap-4">
                {reviews.map((review) => (
                    <li
                        key={review.id}
                        className=""
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-foreground text-sm font-normal font-inter">
                               By <span className="text-primary text-base font-bold"> {review.author}</span>  {review.date}
                            </p>
                        </div>
                        <div className="mt-2 flex items-center gap-0.5">
                            {Array.from({ length: 5 }).map((_, index) => (
                                <Star
                                    key={index}
                                    className={cn(
                                        "size-4",
                                        index < review.rating
                                            ? "fill-review-star text-review-star"
                                            : "text-muted-foreground",
                                    )}
                                />
                            ))}
                        </div>
                        <p className="text-foreground mt-3 text-base font-normal font-inter">
                            {review.body}
                        </p>
                    </li>
                ))}
            </ul>
        </section>
    );
}
