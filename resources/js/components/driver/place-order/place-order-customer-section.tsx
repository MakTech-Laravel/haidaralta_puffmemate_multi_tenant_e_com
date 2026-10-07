import { useHttp } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ResolvedPlaceOrderCustomer } from '@/components/driver/place-order/place-order-types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { lookup } from '@/routes/driver/place-order/customers';

const SEARCH_DEBOUNCE_MS = 300;
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

function looksLikePhone(value: string): boolean {
    const digits = value.replace(/\D/g, '');

    return digits.length >= 6 && /^[\d\s+()-]+$/.test(value.trim());
}

function looksLikeEmail(value: string): boolean {
    return value.includes('@');
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
    const [debouncedQuery, setDebouncedQuery] = useState('');
    const [matches, setMatches] = useState<ResolvedPlaceOrderCustomer[]>([]);
    const requestId = useRef(0);
    const http = useHttp<{ q: string }, LookupResponse>({ q: '' });

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebouncedQuery(query.trim());
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(timer);
    }, [query]);

    useEffect(() => {
        if (customer !== null) {
            return;
        }

        if (debouncedQuery.length < MIN_QUERY_LENGTH) {
            setMatches([]);
            onResolved(null, false);

            return;
        }

        const currentRequest = ++requestId.current;

        http.transform(() => ({ q: debouncedQuery }));

        void http.post(lookup.url(), {
            onSuccess: (response) => {
                if (currentRequest !== requestId.current) {
                    return;
                }

                const found = response?.customers ?? [];
                setMatches(found);
                onResolved(null, found.length === 0);

                if (found.length === 0) {
                    if (looksLikePhone(debouncedQuery)) {
                        onPhoneChange(debouncedQuery);
                        onNameChange('');
                    } else if (looksLikeEmail(debouncedQuery)) {
                        onPhoneChange('');
                        onNameChange('');
                    } else {
                        onNameChange(debouncedQuery);
                        onPhoneChange('');
                    }
                }
            },
            onError: () => {
                if (currentRequest !== requestId.current) {
                    return;
                }

                setMatches([]);
                onResolved(null, false);
            },
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps -- search only when debounced query changes
    }, [debouncedQuery, customer]);

    const selectCustomer = (match: ResolvedPlaceOrderCustomer): void => {
        requestId.current += 1;
        setMatches([]);
        setQuery(match.name);
        onNameChange(match.name);
        onPhoneChange(match.phone);
        onResolved(match, false);
    };

    const clearSelection = (): void => {
        requestId.current += 1;
        setMatches([]);
        setQuery('');
        setDebouncedQuery('');
        onNameChange('');
        onPhoneChange('');
        onResolved(null, false);
    };

    return (
        <div className="space-y-3">
            <div className="grid gap-2">
                <Label htmlFor="customer-search">Search</Label>
                <div className="relative">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        id="customer-search"
                        value={query}
                        autoComplete="off"
                        inputMode="search"
                        className="pr-10 pl-9"
                        placeholder="Name, email, or phone"
                        onChange={(event) => {
                            const next = event.target.value;
                            setQuery(next);

                            if (customer !== null) {
                                onResolved(null, false);
                            }
                        }}
                    />
                    {http.processing ? (
                        <Spinner className="text-muted-foreground absolute top-1/2 right-3 size-4 -translate-y-1/2" />
                    ) : null}
                </div>
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
