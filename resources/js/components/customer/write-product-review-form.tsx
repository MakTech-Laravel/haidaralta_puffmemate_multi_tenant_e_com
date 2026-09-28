import { useForm } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { store as storeProductReview } from '@/routes/customer/product-to-review';

type WriteProductReviewFormProps = {
    itemId: string;
    productName?: string;
    className?: string;
};

export function WriteProductReviewForm({
    itemId,
    productName,
    className,
}: WriteProductReviewFormProps) {
    const { data, setData, post, processing, errors } = useForm<{
        rating: number;
        body: string;
    }>({
        rating: 0,
        body: '',
    });
    const [hoveredRating, setHoveredRating] = useState(0);
    const displayRating = hoveredRating > 0 ? hoveredRating : data.rating;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post(storeProductReview.url(itemId));
    };

    return (
        <form
            onSubmit={submit}
            className={cn(
                'bg-muted w-full min-w-0 rounded-sm p-5 sm:p-8',
                className,
            )}
        >
            <div className="space-y-2">
                <h2 className="text-foreground text-xl font-semibold tracking-tight sm:text-2xl">
                    How was your material quality?
                    {productName ? (
                        <span className="sr-only"> for {productName}</span>
                    ) : null}
                </h2>
                <p className="text-muted-foreground text-base leading-relaxed">
                    Your feedback helps other builders and contractors make
                    better decisions.
                </p>
            </div>

            <div className="mt-8 space-y-3">
                <p className="text-foreground text-xl font-medium">
                    Rate Your Product
                </p>
                <div
                    className="flex items-center gap-1"
                    role="radiogroup"
                    aria-label="Rate your product"
                    onMouseLeave={() => setHoveredRating(0)}
                >
                    {Array.from({ length: 5 }, (_, index) => {
                        const value = index + 1;
                        const selected = value <= displayRating;

                        return (
                            <button
                                key={value}
                                type="button"
                                role="radio"
                                aria-checked={data.rating === value}
                                aria-label={`${value} star${value === 1 ? '' : 's'}`}
                                className="text-muted-foreground hover:text-review-star focus-visible:ring-ring rounded-sm p-0.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                onMouseEnter={() => setHoveredRating(value)}
                                onClick={() => setData('rating', value)}
                            >
                                <Star
                                    className={cn(
                                        'size-8 sm:size-9',
                                        selected
                                            ? 'fill-review-star text-review-star'
                                            : 'text-muted-foreground',
                                    )}
                                />
                            </button>
                        );
                    })}
                </div>
                <InputError message={errors.rating} />
            </div>

            <div className="mt-8 flex flex-col gap-2">
                <Label
                    htmlFor="write-product-review-body"
                    className="text-foreground text-xl font-medium"
                >
                    Review
                </Label>
                <Textarea
                    id="write-product-review-body"
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    placeholder="Write here"
                    className="bg-background min-h-36 resize-y"
                />
                <InputError message={errors.body} />
            </div>

            <Button
                type="submit"
                size="lg"
                disabled={processing}
                className="mt-8 h-11 w-full rounded-xs text-base font-medium"
            >
                Submit
            </Button>
        </form>
    );
}
