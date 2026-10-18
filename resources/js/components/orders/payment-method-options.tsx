import { Banknote, Check, CreditCard, Wallet } from 'lucide-react';
import { cn } from '@/lib/utils';

export type PaymentMethodOption = {
    id: string;
    label: string;
    description?: string | null;
};

type PaymentMethodOptionsProps = {
    methods: PaymentMethodOption[];
    selectedId: string | null;
    onSelect: (id: string) => void;
};

function methodIcon(id: string) {
    if (id === 'pos') {
        return CreditCard;
    }

    if (id === 'cod') {
        return Banknote;
    }

    return Wallet;
}

export function PaymentMethodOptions({
    methods,
    selectedId,
    onSelect,
}: PaymentMethodOptionsProps) {
    return (
        <ul
            className="grid gap-2 sm:grid-cols-3"
            role="radiogroup"
            aria-label="Payment type"
        >
            {methods.map((method) => {
                const selected = selectedId === method.id;
                const Icon = methodIcon(method.id);

                return (
                    <li key={method.id}>
                        <button
                            type="button"
                            role="radio"
                            aria-checked={selected}
                            className={cn(
                                'border-border relative flex w-full items-center gap-3 rounded-2xl border p-3.5 text-left transition-colors sm:flex-col sm:items-start sm:gap-2.5',
                                selected
                                    ? 'border-primary bg-primary/5'
                                    : 'bg-background/60 hover:border-primary/40',
                            )}
                            onClick={() => onSelect(method.id)}
                        >
                            <span
                                className={cn(
                                    'flex size-9 shrink-0 items-center justify-center rounded-xl',
                                    selected
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-foreground',
                                )}
                            >
                                <Icon className="size-4" aria-hidden />
                            </span>
                            <span className="text-foreground min-w-0 flex-1 text-sm font-semibold">
                                {method.label}
                            </span>
                            {selected ? (
                                <Check
                                    className="text-primary size-4 shrink-0 sm:absolute sm:top-3 sm:right-3"
                                    aria-hidden
                                />
                            ) : null}
                        </button>
                    </li>
                );
            })}
        </ul>
    );
}
