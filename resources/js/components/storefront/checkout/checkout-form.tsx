import { usePage } from '@inertiajs/react';
import { CalendarClock, Clock, Zap } from 'lucide-react';
import { useMemo } from 'react';
import AlertError from '@/components/alert-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CheckoutMapDialog } from '@/components/storefront/checkout/checkout-map-dialog';
import { AddressSuggestInput } from '@/components/storefront/wholesale/address-suggest-input';
import {
    deliveryDateOptions,
    filterUpcomingDeliverySlots,
    SAME_DAY_METHOD_ID,
    shiftIsoDate,
    shiftRangeLabel,
    shiftWindowLabel,
} from '@/lib/delivery-shift-availability';
import { cn } from '@/lib/utils';
import type {
    StorefrontDeliveryMethod,
    StorefrontPaymentMethod,
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
    /** Whether the pinned address is inside the same-day radius (null until quoted). */
    sameDayInRadius?: boolean | null;
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
    'bg-card gap-4 rounded-3xl border p-5 shadow-none sm:gap-5 sm:p-8';

const fieldClass = 'h-12 rounded-xl data-[size=default]:h-12';

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

/** Slots the customer can still book for a method (Same day only keeps shifts that have not started). */
function usableSlots(method: StorefrontDeliveryMethod) {
    return method.requiresDate
        ? method.timeSlots
        : filterUpcomingDeliverySlots(method.timeSlots);
}
export function CheckoutForm({
    details,
    hasSavedAddress,
    deliveryMethods,
    paymentMethods,
    errors = {},
    deliveryMethodError,
    sameDayInRadius = null,
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
    const windowLabel = shiftWindowLabel(timeSlots);
    const sameDayMethod = deliveryMethods.find(
        (method) => method.id === SAME_DAY_METHOD_ID,
    );
    const lateMethod = deliveryMethods.find((method) => method.requiresDate);
    // One Day picker: today (Same day) through the last Late date (today + 7 by default).
    const todayIso =
        sameDayMethod?.minDate ??
        (lateMethod?.minDate ? shiftIsoDate(lateMethod.minDate, -1) : null);
    const lastDayIso = lateMethod?.maxDate ?? todayIso;
    const dateOptions = useMemo(
        () => deliveryDateOptions(todayIso, lastDayIso),
        [todayIso, lastDayIso],
    );
    const activeDay =
        details.deliveryMethodId === SAME_DAY_METHOD_ID
            ? todayIso
            : details.deliveryDate;
    const pickDay = (day: string) => {
        if (day === todayIso) {
            if (sameDayMethod) {
                onChange('deliveryMethodId', sameDayMethod.id);
            }

            return;
        }

        if (!lateMethod) {
            return;
        }

        if (details.deliveryMethodId !== lateMethod.id) {
            onChange('deliveryMethodId', lateMethod.id);
        }

        onChange('deliveryDate', day);
    };
    const hasPin = details.latitude !== '' && details.longitude !== '';
    const sameDayOpenToday =
        sameDayMethod !== undefined && usableSlots(sameDayMethod).length > 0;
    // The radius notice shows until a pinned address is confirmed inside the same-day radius.
    const showSameDayNotice = !(hasPin && sameDayInRadius === true);
    // Same day is selected but no shift is left today: the message under the cards explains it.
    const sameDayNoShifts =
        details.deliveryMethodId === SAME_DAY_METHOD_ID &&
        timeSlots.length === 0;
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
                <CardHeader className="px-0">
                    <CardTitle className="text-foreground text-xl font-semibold tracking-tight">
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
                <CardHeader className="px-0">
                    <CardTitle className="text-foreground text-xl font-semibold tracking-tight">
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
                    {hasPin && sameDayMethod && sameDayInRadius === true && sameDayOpenToday ? (
                        <p className="bg-primary-tint text-primary flex items-center gap-2 rounded-2xl px-4 py-3 text-sm">
                            <Zap className="size-4 shrink-0" aria-hidden />
                            <span>
                                <strong className="font-semibold">
                                    Same-day delivery
                                </strong>{' '}
                                · we deliver to your area today
                            </span>
                        </p>
                    ) : null}
                    {isGuest ? null : (
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                className="accent-primary size-4"
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
                <CardHeader className="flex flex-row items-start justify-between gap-3 px-0">
                    <CardTitle className="text-foreground text-xl font-semibold tracking-tight">
                        3. Delivery method
                    </CardTitle>
                    <SectionHint>Choose one</SectionHint>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    {showSameDayNotice ? (
                        <p className="bg-warning-tint text-warning rounded-2xl px-4 py-3 text-sm font-semibold">
                            SAME DAY DELIVERY SERVICE ONLY AVAILABLE WITHIN 15km
                            RADIUS OF COORPAROO, QLD &amp; WEST IPSWICH, QLD.....
                            DONT WORRY WE SHIP AUSTRALIA WIDE! FOR ANY ORDERS
                            OUTSIDE THIS RADIUS, YOU CAN EXPECT YOUR ORDER WITHIN 3
                            BUSINESS DAYS AFTER FULFILLING PAYMENT LINK.
                        </p>
                    ) : null}
                    <div
                        role="radiogroup"
                        aria-label="Delivery method"
                        className="grid gap-3 sm:grid-cols-2"
                    >
                        {deliveryMethods.map((method) => {
                            const selected =
                                details.deliveryMethodId === method.id;
                            // Same day stays selectable even with no shifts left today; it is only tagged and blocked at checkout.
                            const unavailable =
                                method.id === SAME_DAY_METHOD_ID &&
                                usableSlots(method).length === 0;
                            const Icon =
                                method.id === SAME_DAY_METHOD_ID
                                    ? Clock
                                    : CalendarClock;

                            return (
                                <button
                                    key={method.id}
                                    type="button"
                                    role="radio"
                                    aria-checked={selected}
                                    onClick={() =>
                                        onChange(
                                            'deliveryMethodId',
                                            method.id,
                                        )
                                    }
                                    className={cn(
                                        'flex flex-col gap-1.5 rounded-2xl border-2 p-4 text-left transition',
                                        selected
                                            ? 'border-primary bg-primary-tint'
                                            : 'border-border bg-card hover:border-foreground/30',
                                    )}
                                >
                                    <span className="flex items-center gap-3">
                                        <span
                                            className={cn(
                                                'flex size-5 shrink-0 items-center justify-center rounded-full border-2',
                                                selected
                                                    ? 'border-primary bg-primary'
                                                    : 'border-border bg-card',
                                            )}
                                            aria-hidden
                                        >
                                            {selected ? (
                                                <span className="bg-primary-foreground size-2 rounded-full" />
                                            ) : null}
                                        </span>
                                        <Icon
                                            className="text-muted-foreground size-5 shrink-0"
                                            aria-hidden
                                        />
                                        <span className="text-foreground font-semibold">
                                            {method.label}
                                        </span>
                                        {unavailable ? (
                                            <span className="text-muted-foreground ml-auto text-sm">
                                                Unavailable now
                                            </span>
                                        ) : null}
                                    </span>
                                    <span className="text-muted-foreground pl-8 text-sm">
                                        {method.description}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <FieldError
                        message={deliveryMethodError ?? errors.delivery_method}
                    />
                    {(showTimeSlots && !sameDayNoShifts) || dateOptions.length > 0 ? (
                        <div className="space-y-5">
                            {dateOptions.length > 0 ? (
                                <div className="space-y-2">
                                    <p className="text-muted-foreground text-sm font-medium">
                                        Day <RequiredMark />
                                    </p>
                                    <div
                                        role="radiogroup"
                                        aria-label="Delivery day"
                                        className="grid grid-cols-4 gap-2 md:grid-cols-8"
                                    >
                                        {dateOptions.map((option) => {
                                            const active =
                                                activeDay === option.value;

                                            return (
                                                <button
                                                    key={option.value}
                                                    type="button"
                                                    role="radio"
                                                    aria-checked={active}
                                                    onClick={() =>
                                                        pickDay(option.value)
                                                    }
                                                    className={cn(
                                                        'flex w-full min-w-0 flex-col items-center rounded-2xl px-1 py-3 transition',
                                                        active
                                                            ? 'bg-foreground text-background'
                                                            : 'bg-muted text-foreground hover:bg-foreground/10',
                                                    )}
                                                >
                                                    <span className="text-sm opacity-70">
                                                        {option.weekday}
                                                    </span>
                                                    <span className="text-lg font-semibold tabular-nums sm:text-xl">
                                                        {option.label}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                    <FieldError message={errors.delivery_date} />
                                </div>
                            ) : null}
                            {showTimeSlots && !sameDayNoShifts ? (
                                <div className="space-y-2">
                                    <div className="flex items-baseline justify-between gap-3">
                                        <p className="text-muted-foreground text-sm font-medium">
                                            Time <RequiredMark />
                                        </p>
                                        {windowLabel ? (
                                            <p className="text-muted-foreground text-sm">
                                                {windowLabel}{' '}
                                                {showDeliveryDate
                                                    ? 'daily'
                                                    : 'today'}
                                            </p>
                                        ) : null}
                                    </div>
                                    {timeSlots.length > 0 ? (
                                        <div
                                            role="radiogroup"
                                            aria-label="Delivery time"
                                            className="grid grid-cols-2 gap-3 sm:grid-cols-3"
                                        >
                                            {timeSlots.map((slot) => {
                                                const active =
                                                    details.timeSlotId ===
                                                    slot.id;

                                                return (
                                                    <button
                                                        key={slot.id}
                                                        type="button"
                                                        role="radio"
                                                        aria-checked={active}
                                                        title={slot.label}
                                                        onClick={() =>
                                                            onChange(
                                                                'timeSlotId',
                                                                slot.id,
                                                            )
                                                        }
                                                        className={cn(
                                                            'h-12 rounded-2xl border px-3 text-sm font-medium tabular-nums transition',
                                                            active
                                                                ? 'border-primary bg-primary text-primary-foreground'
                                                                : 'border-border bg-card text-foreground hover:border-foreground/30',
                                                        )}
                                                    >
                                                        {shiftRangeLabel(slot)}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground text-sm">
                                            No delivery shifts are available
                                            right now.
                                        </p>
                                    )}
                                    <FieldError message={errors.time_slot_id} />
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className="flex flex-row items-start justify-between gap-3 px-0">
                    <CardTitle className="text-foreground text-xl font-semibold tracking-tight">
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
                                        'flex flex-col items-start gap-2 rounded-2xl border p-4 text-left transition',
                                        selected
                                            ? 'border-primary bg-primary-tint ring-primary ring-1'
                                            : 'border-border bg-card hover:border-foreground/30',
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
