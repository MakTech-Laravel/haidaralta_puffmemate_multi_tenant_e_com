import { MapPin, Phone, Search, UserRound } from 'lucide-react';
import type { DriverPlaceOrderCustomer } from '@/components/driver/place-order/place-order-types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type CustomerFormValues = {
    name: string;
    phone: string;
    address: string;
    locationLabel: string;
};

type PlaceOrderCustomerSectionProps = {
    mode: 'registered' | 'new';
    customers: DriverPlaceOrderCustomer[];
    selectedCustomerId: string | null;
    onSelectCustomer: (customer: DriverPlaceOrderCustomer) => void;
    onClearSelection: () => void;
    values: CustomerFormValues;
    onChange: (field: keyof CustomerFormValues, value: string) => void;
    customerQuery: string;
    onCustomerQueryChange: (value: string) => void;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export function PlaceOrderCustomerSection({
    mode,
    customers,
    selectedCustomerId,
    onSelectCustomer,
    onClearSelection,
    values,
    onChange,
    customerQuery,
    onCustomerQueryChange,
}: PlaceOrderCustomerSectionProps) {
    const q = customerQuery.trim().toLowerCase();
    const filtered =
        q === ''
            ? []
            : customers.filter(
                  (customer) =>
                      customer.name.toLowerCase().includes(q) ||
                      customer.phone.toLowerCase().includes(q) ||
                      customer.address.toLowerCase().includes(q),
              );

    const selectedCustomer =
        selectedCustomerId === null
            ? null
            : (customers.find((customer) => customer.id === selectedCustomerId) ??
              null);

    if (mode === 'registered') {
        if (selectedCustomer) {
            return (
                <div className="border-primary/30 bg-primary/5 flex items-start gap-3 rounded-2xl border p-3">
                    <span className="bg-primary text-primary-foreground flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                        {initials(selectedCustomer.name)}
                    </span>
                    <div className="min-w-0 flex-1 space-y-0.5">
                        <p className="text-primary text-[10px] font-bold tracking-wider uppercase">
                            Selected
                        </p>
                        <p className="text-foreground truncate text-sm font-semibold">
                            {selectedCustomer.name}
                        </p>
                        <p className="text-muted-foreground truncate text-xs">
                            {selectedCustomer.phone}
                        </p>
                        <p className="text-muted-foreground truncate text-xs">
                            {selectedCustomer.address}
                        </p>
                    </div>
                    <button
                        type="button"
                        className="text-primary hover:text-primary/80 shrink-0 text-xs font-bold"
                        onClick={onClearSelection}
                    >
                        Change
                    </button>
                </div>
            );
        }

        return (
            <div className="space-y-3">
                <div className="space-y-1.5">
                    <Label htmlFor="place-order-customer-search">
                        Find customer
                    </Label>
                    <div className="relative">
                        <Search
                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                            aria-hidden
                        />
                        <Input
                            id="place-order-customer-search"
                            value={customerQuery}
                            onChange={(event) =>
                                onCustomerQueryChange(event.target.value)
                            }
                            placeholder="Type a name or phone"
                            autoComplete="off"
                            className="h-12 rounded-2xl pl-10"
                        />
                    </div>
                </div>

                {q === '' ? (
                    <div className="border-border/70 text-muted-foreground flex flex-col items-center gap-2 rounded-2xl border border-dashed px-4 py-8 text-center">
                        <Search className="size-5 opacity-60" aria-hidden />
                        <p className="text-sm font-medium">
                            Type a name or phone to find a customer
                        </p>
                    </div>
                ) : filtered.length === 0 ? (
                    <div className="border-border/70 text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-center text-sm">
                        No registered customers match.
                    </div>
                ) : (
                    <ul className="space-y-2">
                        {filtered.map((customer) => (
                            <li key={customer.id}>
                                <button
                                    type="button"
                                    className="border-border bg-background/60 hover:border-primary/40 flex w-full items-center gap-3 rounded-2xl border p-3 text-left transition-colors"
                                    onClick={() => onSelectCustomer(customer)}
                                >
                                    <span className="bg-muted text-foreground flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                                        {initials(customer.name)}
                                    </span>
                                    <span className="min-w-0 flex-1 space-y-0.5">
                                        <span className="text-foreground block truncate text-sm font-semibold">
                                            {customer.name}
                                        </span>
                                        <span className="text-muted-foreground flex items-center gap-1 truncate text-xs">
                                            <Phone
                                                className="size-3 shrink-0"
                                                aria-hidden
                                            />
                                            {customer.phone}
                                        </span>
                                        <span className="text-muted-foreground flex items-center gap-1 truncate text-xs">
                                            <MapPin
                                                className="size-3 shrink-0"
                                                aria-hidden
                                            />
                                            {customer.locationLabel}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                    <Label htmlFor="place-order-name">Customer name</Label>
                    <div className="relative">
                        <UserRound
                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                            aria-hidden
                        />
                        <Input
                            id="place-order-name"
                            value={values.name}
                            onChange={(event) =>
                                onChange('name', event.target.value)
                            }
                            placeholder="Full name"
                            autoComplete="name"
                            className="h-12 rounded-2xl pl-10"
                        />
                    </div>
                </div>
                <div className="space-y-1.5">
                    <Label htmlFor="place-order-phone">Phone number</Label>
                    <div className="relative">
                        <Phone
                            className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                            aria-hidden
                        />
                        <Input
                            id="place-order-phone"
                            type="tel"
                            value={values.phone}
                            onChange={(event) =>
                                onChange('phone', event.target.value)
                            }
                            placeholder="+61 …"
                            autoComplete="tel"
                            className="h-12 rounded-2xl pl-10"
                        />
                    </div>
                </div>
            </div>
            <div className="space-y-1.5">
                <Label htmlFor="place-order-address">Address</Label>
                <div className="relative">
                    <MapPin
                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2"
                        aria-hidden
                    />
                    <Input
                        id="place-order-address"
                        value={values.address}
                        onChange={(event) =>
                            onChange('address', event.target.value)
                        }
                        placeholder="Street, suburb, postcode"
                        autoComplete="street-address"
                        className="h-12 rounded-2xl pl-10"
                    />
                </div>
            </div>
            <div className="space-y-1.5">
                <Label htmlFor="place-order-location">Location</Label>
                <Input
                    id="place-order-location"
                    value={values.locationLabel}
                    onChange={(event) =>
                        onChange('locationLabel', event.target.value)
                    }
                    placeholder="Area · City"
                    className="h-12 rounded-2xl"
                />
            </div>
        </div>
    );
}
