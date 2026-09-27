import { format, isValid, parse } from 'date-fns';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CheckoutMapDialog } from '@/components/storefront/checkout/checkout-map-dialog';
import { formatAppDate } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import type {
    StorefrontAddress,
    StorefrontDeliveryMethod,
    StorefrontPaymentMethod,
    StorefrontTimeSlot,
} from '@/types/storefront';

export type CheckoutDetails = {
    firstName: string;
    lastName: string;
    mobile: string;
    address: string;
    city: string;
    postalCode: string;
    latitude: string;
    longitude: string;
    addressId: string;
    saveAsDefault: boolean;
    deliveryMethodId: string;
    deliveryDate: string;
    timeSlotId: string;
    paymentMethodId: string;
};

type CheckoutFormProps = {
    details: CheckoutDetails;
    addresses: StorefrontAddress[];
    deliveryMethods: StorefrontDeliveryMethod[];
    paymentMethods: StorefrontPaymentMethod[];
    timeSlots: StorefrontTimeSlot[];
    errors?: Record<string, string>;
    onChange: <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => void;
    className?: string;
};

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="text-destructive text-xs">{message}</p>;
}

const sectionCardClass =
    'bg-accent gap-3 rounded-none border-none p-3 shadow-none sm:gap-4 sm:p-5';

const fieldClass = 'bg-background h-11 rounded-none border-none shadow-none';

const datePickerClass =
    '[&_button]:bg-background [&_button]:h-11 [&_button]:rounded-none [&_button]:border-none [&_button]:shadow-none';

function RequiredMark() {
    return <span className="text-primary">*</span>;
}

function SectionHint({ children }: { children: string }) {
    return (
        <span className="text-muted-foreground text-[10px] font-extrabold tracking-wider uppercase">
            {children}
        </span>
    );
}

function slotHours(slots: StorefrontTimeSlot[]): string | null {
    if (slots.length === 0) {
        return null;
    }

    const first = slots[0].label.split('–')[0]?.trim();
    const lastParts = slots[slots.length - 1].label.split('–');
    const last = lastParts[lastParts.length - 1]?.trim();

    if (!first || !last) {
        return null;
    }

    return `${first} – ${last}`;
}

function deliveryWindowLabel(
    date: string,
    slots: StorefrontTimeSlot[],
): string {
    const hours = slotHours(slots);
    const parsed = date ? parse(date, 'yyyy-MM-dd', new Date()) : null;
    const datePart =
        parsed && isValid(parsed)
            ? `${format(parsed, 'EEEE')} ${formatAppDate(date)}`
            : 'Select a delivery date';

    return hours ? `${datePart} · Delivery hours ${hours}` : datePart;
}

export function CheckoutForm({
    details,
    addresses,
    deliveryMethods,
    paymentMethods,
    timeSlots,
    errors = {},
    onChange,
    className,
}: CheckoutFormProps) {
    const selectedMethod = deliveryMethods.find(
        (method) => method.id === details.deliveryMethodId,
    );
    const showTimeSlots = selectedMethod?.requiresSlot ?? false;
    const today = format(new Date(), 'yyyy-MM-dd');

    const loadAddress = (id: string) => {
        const address = addresses.find((row) => String(row.id) === id);

        if (!address) {
            onChange('addressId', id);
            return;
        }

        const [first, ...rest] = address.receiver_name.split(' ');
        onChange('addressId', id);
        onChange('firstName', first ?? '');
        onChange('lastName', rest.join(' '));
        onChange('mobile', address.phone);
        onChange('address', address.address);
        onChange('city', address.city ?? '');
        onChange('postalCode', address.postal_code ?? '');
        onChange('latitude', address.latitude ?? '');
        onChange('longitude', address.longitude ?? '');
    };

    const errorMessages = Object.values(errors).filter(Boolean);

    return (
        <div className={cn('flex flex-col gap-3 sm:gap-4', className)}>
            {errorMessages.length > 0 ? (
                <Alert variant="destructive">
                    <AlertDescription>
                        <ul className="list-disc space-y-1 pl-4">
                            {errorMessages.map((message) => (
                                <li key={message}>{message}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            ) : null}
            <Card className={sectionCardClass}>
                <CardHeader>
                    <CardTitle className="text-foreground text-lg font-extrabold">
                        1. Your details
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    {addresses.length > 0 ? (
                        <div className="space-y-1.5">
                            <Label>Load saved address</Label>
                            <Select
                                value={details.addressId}
                                onValueChange={loadAddress}
                            >
                                <SelectTrigger className={fieldClass}>
                                    <SelectValue placeholder="Choose a saved address" />
                                </SelectTrigger>
                                <SelectContent>
                                    {addresses.map((address) => (
                                        <SelectItem
                                            key={address.id}
                                            value={String(address.id)}
                                        >
                                            {address.address}
                                            {address.is_default
                                                ? ' (default)'
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    ) : null}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-first-name">
                                First name <RequiredMark />
                            </Label>
                            <Input
                                id="checkout-first-name"
                                value={details.firstName}
                                onChange={(event) =>
                                    onChange('firstName', event.target.value)
                                }
                                placeholder="Jane"
                                autoComplete="given-name"
                                className={fieldClass}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-last-name">
                                Last name <RequiredMark />
                            </Label>
                            <Input
                                id="checkout-last-name"
                                value={details.lastName}
                                onChange={(event) =>
                                    onChange('lastName', event.target.value)
                                }
                                placeholder="Smith"
                                autoComplete="family-name"
                                className={fieldClass}
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="checkout-mobile">
                            Mobile number <RequiredMark />
                        </Label>
                        <Input
                            id="checkout-mobile"
                            type="tel"
                            value={details.mobile}
                            onChange={(event) =>
                                onChange('mobile', event.target.value)
                            }
                            placeholder="0400 000 000"
                            autoComplete="tel"
                            className={fieldClass}
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader>
                    <CardTitle className="text-foreground text-lg font-extrabold">
                        2. Where should we deliver?
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="flex items-end gap-2">
                        <div className="min-w-0 flex-1 space-y-1.5">
                            <Label htmlFor="checkout-address">
                                Address <RequiredMark />
                            </Label>
                            <Input
                                id="checkout-address"
                                value={details.address}
                                onChange={(event) =>
                                    onChange('address', event.target.value)
                                }
                                placeholder="Street address"
                                autoComplete="street-address"
                                className={fieldClass}
                            />
                        </div>
                        <CheckoutMapDialog
                            latitude={details.latitude}
                            longitude={details.longitude}
                            onSelect={({ latitude, longitude, address }) => {
                                onChange('latitude', latitude);
                                onChange('longitude', longitude);
                                if (address) {
                                    onChange('address', address);
                                }
                            }}
                        />
                    </div>
                    {!details.latitude || !details.longitude ? (
                        <Alert variant="destructive">
                            <AlertDescription>
                                Pin your delivery location on the map so we
                                can find the nearest branch and calculate your
                                delivery fee.
                            </AlertDescription>
                        </Alert>
                    ) : null}
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-city">
                                City <RequiredMark />
                            </Label>
                            <Input
                                id="checkout-city"
                                value={details.city}
                                onChange={(event) =>
                                    onChange('city', event.target.value)
                                }
                                placeholder="Suburb or city"
                                autoComplete="address-level2"
                                className={fieldClass}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-postcode">
                                Postal code
                            </Label>
                            <Input
                                id="checkout-postcode"
                                value={details.postalCode}
                                onChange={(event) =>
                                    onChange('postalCode', event.target.value)
                                }
                                placeholder="4000"
                                autoComplete="postal-code"
                                className={fieldClass}
                            />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={details.saveAsDefault}
                            onChange={(event) =>
                                onChange('saveAsDefault', event.target.checked)
                            }
                        />
                        Save as default address
                    </label>
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className="flex flex-row items-start justify-between gap-3">
                    <CardTitle className="text-foreground text-lg font-extrabold">
                        3. Delivery method
                    </CardTitle>
                    <SectionHint>Choose one</SectionHint>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {deliveryMethods.map((method) => {
                            const selected =
                                details.deliveryMethodId === method.id;

                            return (
                                <button
                                    key={method.id}
                                    type="button"
                                    onClick={() =>
                                        onChange('deliveryMethodId', method.id)
                                    }
                                    className={cn(
                                        'bg-background flex items-start gap-3 rounded-none border p-4 text-left',
                                        selected
                                            ? 'border-primary'
                                            : 'border-transparent',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border',
                                            selected
                                                ? 'border-primary'
                                                : 'border-muted-foreground',
                                        )}
                                        aria-hidden
                                    >
                                        {selected ? (
                                            <span className="bg-primary size-2 rounded-full" />
                                        ) : null}
                                    </span>
                                    <span>
                                        <span className="block font-semibold">
                                            {method.label}
                                        </span>
                                        <span className="text-muted-foreground mt-1 block text-sm">
                                            {method.description}
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    {showTimeSlots ? (
                        <div className="space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Local delivery: Same-day service is available
                                within selected delivery zones around
                                participating branches.
                            </p>
                            <div className="space-y-2">
                                <p className="text-foreground text-xs font-extrabold tracking-wider uppercase">
                                    Available time slots
                                </p>
                                <p className="text-muted-foreground text-sm">
                                    {deliveryWindowLabel(
                                        details.deliveryDate,
                                        timeSlots,
                                    )}
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="checkout-delivery-date">
                                        Delivery date <RequiredMark />
                                    </Label>
                                    <DatePicker
                                        id="checkout-delivery-date"
                                        value={details.deliveryDate || null}
                                        onChange={(value) =>
                                            onChange('deliveryDate', value)
                                        }
                                        placeholder="Select a date"
                                        min={today}
                                        className={datePickerClass}
                                        aria-invalid={Boolean(
                                            errors.delivery_date,
                                        )}
                                    />
                                    <FieldError message={errors.delivery_date} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>
                                        Time slot <RequiredMark />
                                    </Label>
                                    <Select
                                        value={details.timeSlotId}
                                        onValueChange={(value) =>
                                            onChange('timeSlotId', value)
                                        }
                                    >
                                        <SelectTrigger className={fieldClass}>
                                            <SelectValue placeholder="Select a time slot" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {timeSlots.map((slot) => (
                                                <SelectItem
                                                    key={slot.id}
                                                    value={slot.id}
                                                >
                                                    {slot.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError message={errors.time_slot_id} />
                                </div>
                            </div>
                        </div>
                    ) : (
                        <Alert>
                            <AlertDescription>
                                Australia-wide shipping does not need a time
                                slot.
                            </AlertDescription>
                        </Alert>
                    )}
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className="flex flex-row items-start justify-between gap-3">
                    <CardTitle className="text-foreground text-lg font-extrabold">
                        4. Payment at delivery
                    </CardTitle>
                    <SectionHint>No online card entry</SectionHint>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {paymentMethods.map((method) => {
                            const selected =
                                details.paymentMethodId === method.id;

                            return (
                                <button
                                    key={method.id}
                                    type="button"
                                    onClick={() =>
                                        onChange('paymentMethodId', method.id)
                                    }
                                    className={cn(
                                        'bg-background flex flex-col items-start gap-2 rounded-none border p-4 text-left',
                                        selected
                                            ? 'border-foreground'
                                            : 'border-transparent',
                                    )}
                                >
                                    {method.badge ? (
                                        <span className="text-foreground text-sm font-semibold">
                                            {method.badge}
                                        </span>
                                    ) : null}
                                    <span className="block font-semibold">
                                        {method.title ?? method.label}
                                    </span>
                                    <span className="text-muted-foreground text-sm">
                                        {method.description}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <p className="text-muted-foreground text-sm">
                        Have your chosen payment method ready when the driver
                        arrives.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
