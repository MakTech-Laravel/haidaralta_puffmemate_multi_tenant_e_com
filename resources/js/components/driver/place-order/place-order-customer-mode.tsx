import { cn } from '@/lib/utils';
import type { CustomerMode } from '@/components/driver/place-order/place-order-types';

type PlaceOrderCustomerModeProps = {
    mode: CustomerMode;
    onChange: (mode: CustomerMode) => void;
};

const OPTIONS: Array<{ id: CustomerMode; label: string }> = [
    { id: 'registered', label: 'Registered' },
    { id: 'new', label: 'New customer' },
];

export function PlaceOrderCustomerMode({
    mode,
    onChange,
}: PlaceOrderCustomerModeProps) {
    return (
        <div
            className="bg-muted/80 grid grid-cols-2 gap-1 rounded-2xl p-1.5"
            role="tablist"
            aria-label="Customer type"
        >
            {OPTIONS.map((option) => {
                const active = mode === option.id;

                return (
                    <button
                        key={option.id}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        className={cn(
                            'rounded-xl px-3 py-3 text-sm font-semibold transition-colors',
                            active
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                        onClick={() => onChange(option.id)}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
