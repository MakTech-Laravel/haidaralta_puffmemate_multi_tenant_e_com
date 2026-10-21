import { Minus, Plus, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type PackBuyControlsProps = {
    stockLeft?: number | null;
    buyLabel?: string;
    className?: string;
    processing?: boolean;
    onBuyNow?: (quantity: number) => void;
};

export function PackBuyControls({
    stockLeft,
    buyLabel = 'Buy now',
    className,
    processing = false,
    onBuyNow,
}: PackBuyControlsProps) {
    const maxQuantity = Math.max(1, stockLeft ?? 1);
    const [quantity, setQuantity] = useState(1);

    const changeQuantity = (next: number) => {
        setQuantity(Math.min(maxQuantity, Math.max(1, next)));
    };

    return (
        <div className={cn('flex items-center gap-3', className)}>
            <div className="border-border flex h-11 shrink-0 items-center rounded-md border">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-11 rounded-none"
                    onClick={() => changeQuantity(quantity - 1)}
                    disabled={processing || quantity <= 1}
                    aria-label="Decrease quantity"
                >
                    <Minus className="size-4" />
                </Button>
                <span className="w-8 text-center text-sm font-semibold tabular-nums">
                    {quantity}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-11 rounded-none"
                    onClick={() => changeQuantity(quantity + 1)}
                    disabled={processing || quantity >= maxQuantity}
                    aria-label="Increase quantity"
                >
                    <Plus className="size-4" />
                </Button>
            </div>
            <Button
                type="button"
                className="h-11 flex-1 rounded-md text-sm font-bold tracking-wide uppercase"
                disabled={processing || (stockLeft !== null && stockLeft !== undefined && stockLeft < 1)}
                onClick={() => onBuyNow?.(quantity)}
            >
                <ShoppingCart className="size-4" />
                {processing ? 'Redirecting…' : buyLabel}
            </Button>
        </div>
    );
}
