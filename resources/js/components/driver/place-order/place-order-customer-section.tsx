import { useHttp } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { ResolvedPlaceOrderCustomer } from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { lookup } from '@/routes/driver/place-order/customers';

const MIN_QUERY_LENGTH = 2;

type PlaceOrderCustomerSectionProps = {
    phone: string;
    name: string;
    customer: ResolvedPlaceOrderCustomer | null;
    needsAdd: boolean;
    onPhoneChange: (phone: string) => void;
    onNameChange: (name: string) => void;
    onResolved: (
        customer: ResolvedPlaceOrderCustomer | null,
        needsAdd: boolean,
    ) => void;
};

type LookupResponse = {
    customers: ResolvedPlaceOrderCustomer[];
};

/** Digits / + / leading 0 → treat as phone, never dump into Name. */
function looksLikePhone(value: string): boolean {
    const trimmed = value.trim();

    if (trimmed === '') {
        return false;
    }

    if (!/^[\d\s+()-]+$/.test(trimmed)) {
        return false;
    }

    const digits = trimmed.replace(/\D/g, '');

    return digits.length >= 2 && (/^[+0]/.test(trimmed) || /^\d/.test(trimmed));
}

function looksLikeEmail(value: string): boolean {
    return value.includes('@');
}

function prefillAddForm(
    query: string,
    onPhoneChange: (phone: string) => void,
    onNameChange: (name: string) => void,
): void {
    if (looksLikePhone(query)) {
        onPhoneChange(query);
        onNameChange('');

        return;
    }

    if (looksLikeEmail(query)) {
        onPhoneChange('');
        onNameChange('');

        return;
    }

    onNameChange(query);
    onPhoneChange('');
}

export function PlaceOrderCustomerSection({
    phone,
    name,
    customer,
    needsAdd,
    onPhoneChange,
    onNameChange,
    onResolved,
}: PlaceOrderCustomerSectionProps) {
    const [query, setQuery] = useState('');
    const [matches, setMatches] = useState<ResolvedPlaceOrderCustomer[]>([]);
    const [error, setError] = useState<string | null>(null);
    const http = useHttp<{ q: string }, LookupResponse>({ q: '' });

    const lookupCustomer = (): void => {
        const trimmed = query.trim();
        setError(null);
        setMatches([]);
        onResolved(null, false);
        onNameChange('');
        onPhoneChange('');

        if (trimmed.length < MIN_QUERY_LENGTH) {
            setError('Enter at least 2 characters to look up.');

            return;
        }

        http.transform(() => ({ q: trimmed }));

        void http.post(lookup.url(), {
            onSuccess: (response) => {
                const found = response?.customers ?? [];
                setMatches(found);

                if (found.length === 0) {
                    prefillAddForm(trimmed, onPhoneChange, onNameChange);
                    onResolved(null, true);

                    return;
                }

                onResolved(null, false);
            },
            onError: () => {
                setMatches([]);
                setError('Lookup failed. Try again.');
                onResolved(null, false);
            },
        });
    };

    const selectCustomer = (match: ResolvedPlaceOrderCustomer): void => {
        setMatches([]);
        setError(null);
        setQuery(match.name);
        onNameChange(match.name);
        onPhoneChange(match.phone);
        onResolved(match, false);
    };

    const clearSelection = (): void => {
        setMatches([]);
        setError(null);
        setQuery('');
        onNameChange('');
        onPhoneChange('');
        onResolved(null, false);
    };

    return (
        <div className="space-y-3">
            <div className="grid gap-2">
                <Label htmlFor="customer-search">Search</Label>
                <div className="flex gap-2">
                    <div className="relative min-w-0 flex-1">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            id="customer-search"
                            value={query}
                            autoComplete="off"
                            inputMode="search"
                            aria-busy={http.processing}
                            className="pl-9"
                            placeholder="Name, email, or phone"
                            onChange={(event) => {
                                setQuery(event.target.value);
                                setError(null);

                                if (customer !== null || needsAdd) {
                                    setMatches([]);
                                    onResolved(null, false);
                                }
                            }}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    lookupCustomer();
                                }
                            }}
                        />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={http.processing || query.trim() === ''}
                        onClick={lookupCustomer}
                    >
                        {http.processing ? <Spinner /> : null}
                        Look up
                    </Button>
                </div>
                {error ? (
                    <p className="text-destructive text-xs">{error}</p>
                ) : null}
            </div>

            {customer ? (
                <div className="border-primary/30 bg-primary/5 space-y-2 rounded-2xl border p-3">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className="text-primary text-[10px] font-bold tracking-wider uppercase">
                                {customer.registration_status === 'registered'
                                    ? 'Registered customer'
                                    : 'Provisional customer'}
                            </p>
                            <p className="text-foreground text-sm font-semibold">
                                {customer.name}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {[customer.phone, customer.email]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        <button
                            type="button"
                            className="text-muted-foreground hover:text-foreground shrink-0 text-xs font-medium underline-offset-2 hover:underline"
                            onClick={clearSelection}
                        >
                            Change
                        </button>
                    </div>
                </div>
            ) : null}

            {!customer && matches.length > 0 ? (
                <ul className="border-border/80 divide-border/80 max-h-48 overflow-y-auto rounded-2xl border">
                    {matches.map((match) => (
                        <li key={match.id}>
                            <button
                                type="button"
                                className={cn(
                                    'hover:bg-muted/60 flex w-full flex-col items-start gap-0.5 px-3 py-2.5 text-left transition-colors',
                                )}
                                onClick={() => selectCustomer(match)}
                            >
                                <span className="text-foreground text-sm font-semibold">
                                    {match.name}
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {[match.phone, match.email]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            ) : null}

            {needsAdd ? (
                <div className="space-y-3">
                    <p className="text-muted-foreground text-xs">
                        No matching customer. Add a name and Australian mobile —
                        a provisional account is created with the sale.
                    </p>
                    <div className="grid gap-2">
                        <Label htmlFor="customer-name">Name</Label>
                        <Input
                            id="customer-name"
                            value={name}
                            autoComplete="name"
                            placeholder="Customer name"
                            onChange={(event) =>
                                onNameChange(event.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="customer-phone">Phone</Label>
                        <Input
                            id="customer-phone"
                            value={phone}
                            inputMode="tel"
                            autoComplete="tel"
                            placeholder="04xx xxx xxx"
                            onChange={(event) =>
                                onPhoneChange(event.target.value)
                            }
                        />
                    </div>
                </div>
            ) : null}
        </div>
    );
}
