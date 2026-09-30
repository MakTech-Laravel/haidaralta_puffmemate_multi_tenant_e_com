import { Phone, Plus, Search, UserRound } from 'lucide-react';
import type { DriverPlaceOrderCustomer } from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type CustomerFormValues = {
    name: string;
    phone: string;
};

type PlaceOrderCustomerSectionProps = {
    customers: DriverPlaceOrderCustomer[];
    selectedCustomerId: string | null;
    isAddingNew: boolean;
    onSelectCustomer: (customer: DriverPlaceOrderCustomer) => void;
    onClearSelection: () => void;
    onStartAdd: (query: string) => void;
    onCancelAdd: () => void;
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

export function looksLikePhone(query: string): boolean {
    const digits = query.replace(/[\s+\-().]/g, '');

    return digits.length >= 3 && /^\d+$/.test(digits);
}

export function PlaceOrderCustomerSection({
    customers,
    selectedCustomerId,
    isAddingNew,
    onSelectCustomer,
    onClearSelection,
    onStartAdd,
    onCancelAdd,
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
                      customer.phone.toLowerCase().includes(q),
              );

    const selectedCustomer =
        selectedCustomerId === null
            ? null
            : (customers.find((customer) => customer.id === selectedCustomerId) ??
              null);

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

    if (isAddingNew) {
        return (
            <div className="space-y-3">
                <div className="flex items-center justify-between gap-2">
                    <p className="text-foreground text-xs font-bold tracking-wide uppercase">
                        New customer
                    </p>
                    <button
                        type="button"
                        className="text-primary hover:text-primary/80 shrink-0 text-xs font-bold"
                        onClick={onCancelAdd}
                    >
                        Change
                    </button>
                </div>
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
                <div className="border-border/70 flex flex-col items-center gap-3 rounded-2xl border border-dashed px-4 py-6 text-center">
                    <p className="text-muted-foreground text-sm">
                        No customers match. Add them to continue.
                    </p>
                    <Button
                        type="button"
                        size="sm"
                        className="rounded-xl"
                        onClick={() => onStartAdd(customerQuery.trim())}
                    >
                        <Plus className="size-4" aria-hidden />
                        Add
                    </Button>
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
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
