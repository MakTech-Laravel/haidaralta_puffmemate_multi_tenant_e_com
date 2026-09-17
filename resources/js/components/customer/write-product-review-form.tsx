import { Star } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type WriteProductReviewFormProps = {
    productName?: string;
    className?: string;
};

export function WriteProductReviewForm({
    productName,
    className,
}: WriteProductReviewFormProps) {
    const [rating, setRating] = useState(0);
    const [hoveredRating, setHoveredRating] = useState(0);
    const [body, setBody] = useState('');

    const displayRating = hoveredRating > 0 ? hoveredRating : rating;

    const submit = () => {
        setRating(0);
        setHoveredRating(0);
        setBody('');
    };

    return (
        <div
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
                                aria-checked={rating === value}
                                aria-label={`${value} star${value === 1 ? '' : 's'}`}
                                className="text-muted-foreground hover:text-review-star rounded-sm p-0.5 transition-colors focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none"
                                onMouseEnter={() => setHoveredRating(value)}
                                onClick={() => setRating(value)}
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
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    placeholder="Write here"
                    className="bg-background min-h-36 resize-y"
                />
            </div>

            <Button
                type="button"
                size="lg"
                className="mt-8 h-11 w-full rounded-xs text-base font-medium"
                onClick={submit}
            >
                Submit
            </Button>
        </div>
    );
}
