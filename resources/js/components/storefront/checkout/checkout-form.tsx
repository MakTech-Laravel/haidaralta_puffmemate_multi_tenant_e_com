import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import AlertError from '@/components/alert-error';
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
import { AddressSuggestInput } from '@/components/storefront/wholesale/address-suggest-input';
import { filterUpcomingDeliverySlots } from '@/lib/delivery-shift-availability';
import { cn } from '@/lib/utils';
import type {
    StorefrontDeliveryMethod,
    StorefrontPaymentMethod,
    StorefrontTimeSlot,
} from '@/types/storefront';

const AU_COUNTRY = 'au';

/**
 * Error keys that render under their own control (here or in the order summary), or are
 * helper data rather than a message. Anything else falls back to the alert at the top.
 */
const FIELD_ERROR_KEYS = new Set([
    'name',
    'mobile',
    'address',
    'latitude',
    'longitude',
    'city',
    'postal_code',
    'save_as_default',
    'delivery_method',
    'delivery_date',
    'time_slot_id',
    'payment_method',
    'coupon',
    'terms',
    'accept_late_delivery',
    'late_delivery_products',
]);

export type CheckoutDetails = {
    name: string;
    mobile: string;
    address: string;
    city: string;
    postalCode: string;
    latitude: string;
    longitude: string;
    saveAsDefault: boolean;
    deliveryMethodId: string;
    deliveryDate: string;
    timeSlotId: string;
    paymentMethodId: string;
};

type CheckoutFormProps = {
    details: CheckoutDetails;
    hasSavedAddress: boolean;
    deliveryMethods: StorefrontDeliveryMethod[];
    paymentMethods: StorefrontPaymentMethod[];
    errors?: Record<string, string>;
    /** Shown under the delivery method cards (server error or the Same day / late-items conflict). */
    deliveryMethodError?: string;
    onChange: <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => void;
    onPinSelect?: (
        latitude: string,
        longitude: string,
        address?: string,
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

function RequiredMark() {
    return <span className="text-primary">*</span>;
}

function SectionHint({ children }: { children: string }) {
    return (
        <span className="text-muted-foreground text-[10px] font-semibold tracking-wider uppercase">
            {children}
        </span>
    );
}

function FieldHint({ children }: { children: string }) {
    return <p className="text-muted-foreground text-xs">{children}</p>;
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

function deliveryWindowLabel(slots: StorefrontTimeSlot[]): string {
    const hours = slotHours(slots);

    return hours
        ? `Preferred window ${hours}`
        : 'Choose a preferred time window';
}

export function CheckoutForm({
    details,
    hasSavedAddress,
    deliveryMethods,
    paymentMethods,
    errors = {},
    deliveryMethodError,
    onChange,
    onPinSelect,
    className,
}: CheckoutFormProps) {
    const { portal } = usePage<{ portal: string | null }>().props;
    // Staff portal users check out the guest way, so only customers save an address.
    const isGuest = portal !== 'customer';
    const selectedMethod = deliveryMethods.find(
        (method) => method.id === details.deliveryMethodId,
    );
    const showTimeSlots = selectedMethod?.requiresSlot ?? false;
    const showDeliveryDate = selectedMethod?.requiresDate ?? false;
    const timeSlots = useMemo(() => {
        const slots = selectedMethod?.timeSlots ?? [];

        if (showDeliveryDate) {
            return slots;
        }

        return filterUpcomingDeliverySlots(slots);
    }, [selectedMethod?.timeSlots, showDeliveryDate]);
    const paymentHint = paymentMethods.some(
        (method) => method.id === 'payment_link',
    )
        ? null
        : paymentMethods.every(
                (method) => method.id === 'cod' || method.id === 'pos',
            )
          ? 'Pay on delivery'
          : null;

    const errorMessages = Object.entries(errors).flatMap(([key, value]) => {
        if (FIELD_ERROR_KEYS.has(key)) {
            return [];
        }

        if (typeof value === 'string' && value.trim() !== '') {
            return [value];
        }

        if (Array.isArray(value)) {
            return value.filter(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            );
        }

        return [];
    });

    return (
        <div className={cn('flex flex-col gap-3 sm:gap-4', className)}>
            {errorMessages.length > 0 ? (
                <AlertError
                    title="Please fix the following."
                    errors={errorMessages}
                />
            ) : null}
            <Card className={sectionCardClass}>
                <CardHeader>
                    <CardTitle className="text-foreground text-lg font-semibold">
                        1. Your details
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="grid gap-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-full-name">
                                What name should we use for your order?{' '}
                                <RequiredMark />
                            </Label>
                            <FieldHint>
                                You may use any name or alias to maintain your
                                privacy.
                            </FieldHint>
                            <Input
                                id="checkout-full-name"
                                value={details.name}
                                onChange={(event) =>
                                    onChange('name', event.target.value)
                                }
                                placeholder="Enter full name"
                                autoComplete="name"
                                aria-invalid={Boolean(errors.name)}
                                className={fieldClass}
                            />
                            <FieldError message={errors.name} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-mobile">
                                Phone Number ( EXPECT CALL FROM PRIVATE
                                NUMBER!!! must be reachable during delivery
                                time) <RequiredMark />
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
                                aria-invalid={Boolean(errors.mobile)}
                                className={fieldClass}
                            />
                            <p className="text-muted-foreground text-xs">
                                Australian mobile numbers only.
                            </p>
                            <FieldError message={errors.mobile} />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader>
                    <CardTitle className="text-foreground text-lg font-semibold">
                        2. Where should we deliver?
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="flex items-end gap-2">
                        <div className="min-w-0 flex-1 space-y-1.5">
                            <Label htmlFor="checkout-address">
                                Where would you like your order delivered?{' '}
                                <RequiredMark />
                            </Label>
                            <FieldHint>
                                For Privacy you may enter only the street and
                                suburb , or provide a nearby meeting location
                                for the driver.
                            </FieldHint>
                            <AddressSuggestInput
                                id="checkout-address"
                                name="address"
                                value={details.address}
                                countrycodes={AU_COUNTRY}
                                placeholder="Start typing an Australian address"
                                className={fieldClass}
                                onSelect={({
                                    address,
                                    latitude,
                                    longitude,
                                    city,
                                    postalCode,
                                }) => {
                                    onChange('address', address);
                                    if (city) {
                                        onChange('city', city);
                                    }
                                    if (postalCode) {
                                        onChange('postalCode', postalCode);
                                    }

                                    if (onPinSelect) {
                                        onPinSelect(
                                            latitude,
                                            longitude,
                                            address,
                                        );
                                        return;
                                    }

                                    onChange('latitude', latitude);
                                    onChange('longitude', longitude);
                                }}
                                onClear={() => {
                                    onChange('address', '');
                                    onChange('latitude', '');
                                    onChange('longitude', '');
                                }}
                            />
                            <FieldError message={errors.address} />
                        </div>
                        <CheckoutMapDialog
                            latitude={details.latitude}
                            longitude={details.longitude}
                            countrycodes={AU_COUNTRY}
                            onSelect={({
                                latitude,
                                longitude,
                                address,
                                city,
                                postalCode,
                            }) => {
                                if (city) {
                                    onChange('city', city);
                                }
                                if (postalCode) {
                                    onChange('postalCode', postalCode);
                                }

                                if (onPinSelect) {
                                    onPinSelect(latitude, longitude, address);
                                    return;
                                }

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
                    <FieldError message={errors.latitude ?? errors.longitude} />
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
                                aria-invalid={Boolean(errors.city)}
                                className={fieldClass}
                            />
                            <FieldError message={errors.city} />
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
                                aria-invalid={Boolean(errors.postal_code)}
                                className={fieldClass}
                            />
                            <FieldError message={errors.postal_code} />
                        </div>
                    </div>
                    {isGuest ? null : (
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={details.saveAsDefault}
                                onChange={(event) =>
                                    onChange(
                                        'saveAsDefault',
                                        event.target.checked,
                                    )
                                }
                            />
                            {hasSavedAddress
                                ? 'Update my default address with these details'
                                : 'Save as default address'}
                        </label>
                    )}
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className="flex flex-row items-start justify-between gap-3">
                    <CardTitle className="text-foreground text-lg font-semibold">
                        3. Delivery method
                    </CardTitle>
                    <SectionHint>Choose one</SectionHint>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <p className="text-muted-foreground text-sm font-semibold">
                        SAME DAY DELIVERY SERVICE ONLY AVAILABLE WITHIN 15km
                        RADIUS OF COORPAROO, QLD &amp; WEST IPSWICH, QLD.....
                        DONT WORRY WE SHIP AUSTRALIA WIDE! FOR ANY ORDERS
                        OUTSIDE THIS RADIUS, YOU CAN EXPECT YOUR ORDER WITHIN 3
                        BUSINESS DAYS AFTER FULFILLING PAYMENT LINK.
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {deliveryMethods.map((method) => {
                            const selected =
                                details.deliveryMethodId === method.id;

                            return (
                                <button
                                    key={method.id}
                                    type="button"
                                    onClick={() =>
                                        onChange(
                                            'deliveryMethodId',
                                            method.id,
                                        )
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
                    <FieldError
                        message={deliveryMethodError ?? errors.delivery_method}
                    />
                    {showTimeSlots || showDeliveryDate ? (
                        <div className="space-y-4">
                            <p className="text-muted-foreground text-sm">
                                {showDeliveryDate
                                    ? 'Choose a future delivery date and one shift.'
                                    : 'Choose one remaining delivery shift for today.'}
                            </p>
                            {showDeliveryDate ? (
                                <div className="border-border/80 bg-background space-y-3 rounded-none border p-4">
                                    <div className="space-y-1">
                                        <p className="text-foreground text-xs font-semibold tracking-wider uppercase">
                                            Delivery date
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            Pick a day from tomorrow through the
                                            next two weeks.
                                        </p>
                                    </div>
                                    <div className="max-w-sm space-y-1.5">
                                        <Label htmlFor="delivery_date">
                                            Date <RequiredMark />
                                        </Label>
                                        <DatePicker
                                            id="delivery_date"
                                            value={
                                                details.deliveryDate || null
                                            }
                                            onChange={(value) =>
                                                onChange(
                                                    'deliveryDate',
                                                    value ?? '',
                                                )
                                            }
                                            placeholder="Select delivery date"
                                            clearable={false}
                                            className="w-full"
                                            min={
                                                selectedMethod?.minDate ??
                                                undefined
                                            }
                                            max={
                                                selectedMethod?.maxDate ??
                                                undefined
                                            }
                                            aria-invalid={Boolean(
                                                errors.delivery_date,
                                            )}
                                        />
                                        <FieldError
                                            message={errors.delivery_date}
                                        />
                                    </div>
                                </div>
                            ) : null}
                            {showTimeSlots ? (
                                <div className="space-y-2">
                                    <p className="text-foreground text-xs font-semibold tracking-wider uppercase">
                                        Available delivery shifts
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        {deliveryWindowLabel(timeSlots)}
                                    </p>
                                    <div className="max-w-sm space-y-1.5">
                                        <Label>
                                            Delivery shift <RequiredMark />
                                        </Label>
                                        <Select
                                            value={details.timeSlotId}
                                            onValueChange={(value) =>
                                                onChange('timeSlotId', value)
                                            }
                                        >
                                            <SelectTrigger
                                                className={fieldClass}
                                            >
                                                <SelectValue placeholder="Select a delivery shift" />
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
                                        <FieldError
                                            message={errors.time_slot_id}
                                        />
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className="flex flex-row items-start justify-between gap-3">
                    <CardTitle className="text-foreground text-lg font-semibold">
                        4. Payment method
                    </CardTitle>
                    {paymentHint ? (
                        <SectionHint>{paymentHint}</SectionHint>
                    ) : null}
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
                    <FieldError message={errors.payment_method} />
                    <p className="text-muted-foreground text-sm">
                        Have your chosen payment method ready when the driver
                        arrives.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
