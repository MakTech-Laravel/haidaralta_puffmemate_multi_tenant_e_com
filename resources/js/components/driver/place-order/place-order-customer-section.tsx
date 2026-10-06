import { useHttp } from '@inertiajs/react';
import { Phone } from 'lucide-react';
import { useState } from 'react';
import { lookup } from '@/routes/driver/place-order/customers';
import type { ResolvedPlaceOrderCustomer } from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type PlaceOrderCustomerSectionProps = {
    phone: string;
    name: string;
    customer: ResolvedPlaceOrderCustomer | null;
    needsName: boolean;
    onPhoneChange: (phone: string) => void;
    onNameChange: (name: string) => void;
    onResolved: (customer: ResolvedPlaceOrderCustomer | null, needsName: boolean) => void;
};

export function PlaceOrderCustomerSection({
    phone,
    name,
    customer,
    needsName,
    onPhoneChange,
    onNameChange,
    onResolved,
}: PlaceOrderCustomerSectionProps) {
    const http = useHttp<{ phone: string }, { customer: ResolvedPlaceOrderCustomer | null }>({
        phone: '',
    });
    const [error, setError] = useState<string | null>(null);

    const lookupCustomer = (): void => {
        setError(null);
        onResolved(null, false);
        http.transform(() => ({ phone }));

        void http.post(lookup.url(), {
            onSuccess: (response) => {
                const found = response?.customer ?? null;

                if (found) {
                    onNameChange(found.name);
                    onResolved(found, false);

                    return;
                }

                onNameChange('');
                onResolved(null, true);
            },
            onError: () => {
                setError('Enter a valid Australian mobile number.');
            },
        });
    };

    return (
        <div className="space-y-3">
            <div className="grid gap-2">
                <Label htmlFor="customer-phone">Phone</Label>
                <div className="flex gap-2">
                    <div className="relative min-w-0 flex-1">
                        <Phone className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            id="customer-phone"
                            value={phone}
                            inputMode="tel"
                            autoComplete="tel"
                            className="pl-9"
                            placeholder="04xx xxx xxx"
                            onChange={(event) => {
                                onPhoneChange(event.target.value);
                                onResolved(null, false);
                            }}
                        />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={http.processing || phone.trim() === ''}
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
                <div className="border-primary/30 bg-primary/5 rounded-2xl border p-3">
                    <p className="text-primary text-[10px] font-bold tracking-wider uppercase">
                        {customer.registration_status === 'registered'
                            ? 'Registered customer'
                            : 'Provisional customer'}
                    </p>
                    <p className="text-foreground text-sm font-semibold">
                        {customer.name}
                    </p>
                    <p className="text-muted-foreground text-xs">{customer.phone}</p>
                </div>
            ) : null}

            {needsName ? (
                <div className="grid gap-2">
                    <Label htmlFor="customer-name">Name</Label>
                    <Input
                        id="customer-name"
                        value={name}
                        autoComplete="name"
                        placeholder="Customer name"
                        onChange={(event) => onNameChange(event.target.value)}
                    />
                    <p className="text-muted-foreground text-xs">
                        No account uses this number. A provisional customer will
                        be created with the sale.
                    </p>
                </div>
            ) : null}
        </div>
    );
}
