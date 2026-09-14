import { CalendarDays } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    CheckoutDeliveryMethod,
    CheckoutDetails,
    CheckoutPaymentMethod,
    CheckoutTimeSlot,
} from '@/lib/storefront-checkout';
import { cn } from '@/lib/utils';

type CheckoutFormProps = {
    details: CheckoutDetails;
    deliveryMethods: CheckoutDeliveryMethod[];
    paymentMethods: CheckoutPaymentMethod[];
    timeSlots: CheckoutTimeSlot[];
    deliveryAlert: string;
    onChange: <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => void;
    className?: string;
};

const sectionCardClass =
    'bg-accent gap-3 rounded-none border-none p-3 shadow-none sm:gap-4 sm:p-5';

const sectionHeaderClass =
    'flex-row flex-wrap items-center justify-between gap-2 space-y-0 px-0 sm:gap-3';

const dateInputClass =
    'h-11 rounded-none border-none bg-background pr-10 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0';

function SectionBadge({ children }: { children: string }) {
    return (
        <span className="text-muted-foreground text-[0.65rem] font-extrabold tracking-wider uppercase">
            {children}
        </span>
    );
}

export function CheckoutForm({
    details,
    deliveryMethods,
    paymentMethods,
    timeSlots,
    deliveryAlert,
    onChange,
    className,
}: CheckoutFormProps) {
    const showTimeSlots = details.deliveryMethodId === 'same-day';

    return (
        <div className={cn('flex flex-col gap-3 sm:gap-4', className)}>
            <Card className={sectionCardClass}>
                <CardHeader className={sectionHeaderClass}>
                    <CardTitle className="text-foreground text-lg font-extrabold sm:text-xl md:text-2xl">
                        1. Your details
                    </CardTitle>
                    <SectionBadge>Required</SectionBadge>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label
                                htmlFor="checkout-first-name"
                                className="text-foreground text-xs font-extrabold uppercase"
                            >
                                First name
                            </Label>
                            <Input
                                id="checkout-first-name"
                                value={details.firstName}
                                onChange={(event) =>
                                    onChange('firstName', event.target.value)
                                }
                                placeholder="First name"
                                className="h-11 rounded-none border-none bg-background"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label
                                htmlFor="checkout-last-name"
                                className="text-foreground text-xs font-extrabold uppercase"
                            >
                                Last name / Alias
                            </Label>
                            <Input
                                id="checkout-last-name"
                                value={details.lastName}
                                onChange={(event) =>
                                    onChange('lastName', event.target.value)
                                }
                                placeholder="Last name or alias"
                                className="h-11 rounded-none border-none bg-background"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label
                            htmlFor="checkout-mobile"
                            className="text-foreground text-xs font-extrabold uppercase"
                        >
                            Mobile number
                        </Label>
                        <Input
                            id="checkout-mobile"
                            type="tel"
                            value={details.mobile}
                            onChange={(event) =>
                                onChange('mobile', event.target.value)
                            }
                            placeholder="04XX XXX XXX"
                            className="h-11 rounded-none border-none bg-background"
                        />
                    </div>
                    <p className="text-muted-foreground text-xs leading-relaxed">
                        We only use your details for delivery. Prefer privacy?
                        Use an alias for last name.
                    </p>
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className={sectionHeaderClass}>
                    <CardTitle className="text-foreground text-lg font-extrabold sm:text-xl md:text-2xl">
                        2. Where should we deliver?
                    </CardTitle>
                    <SectionBadge>Required</SectionBadge>
                </CardHeader>
                <CardContent className="px-0">
                    <div className="space-y-1.5">
                        <Label
                            htmlFor="checkout-location"
                            className="sr-only"
                        >
                            Search location
                        </Label>
                        <Input
                            id="checkout-location"
                            value={details.location}
                            onChange={(event) =>
                                onChange('location', event.target.value)
                            }
                            placeholder="Search Location"
                            className="h-11 rounded-none border-none bg-background"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className={sectionHeaderClass}>
                    <CardTitle className="text-foreground text-lg font-extrabold sm:text-xl md:text-2xl">
                        3. Delivery method
                    </CardTitle>
                    <SectionBadge>Choose one</SectionBadge>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div
                        role="radiogroup"
                        aria-label="Delivery method"
                        className="grid gap-3 sm:grid-cols-2"
                    >
                        {deliveryMethods.map((method) => {
                            const selected =
                                details.deliveryMethodId === method.id;

                            return (
                                <button
                                    key={method.id}
                                    type="button"
                                    role="radio"
                                    aria-checked={selected}
                                    onClick={() =>
                                        onChange('deliveryMethodId', method.id)
                                    }
                                    className={cn(
                                        'bg-background rounded-none p-3 text-left transition sm:p-4',
                                        selected &&
                                            'border-primary ring-primary/20 ring-1',
                                    )}
                                >
                                    <span className="flex items-start gap-3">
                                        <span
                                            className={cn(
                                                'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border',
                                                selected
                                                    ? 'border-primary'
                                                    : 'border-muted-foreground/40',
                                            )}
                                            aria-hidden
                                        >
                                            {selected ? (
                                                <span className="bg-primary size-2 rounded-full" />
                                            ) : null}
                                        </span>
                                        <span>
                                            <span className="text-foreground block text-sm font-bold">
                                                {method.title}
                                            </span>
                                            <span className="text-muted-foreground mt-1 block text-xs leading-relaxed">
                                                {method.description}
                                            </span>
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    {showTimeSlots ? (
                        <>
                            <Alert className="border-primary/20 bg-primary/5 text-primary [&>svg]:text-primary">
                                <AlertDescription className="text-primary text-xs leading-relaxed">
                                    {deliveryAlert}
                                </AlertDescription>
                            </Alert>

                            <div className="space-y-3">
                                <p className="text-muted-foreground text-xs font-extrabold tracking-wider uppercase">
                                    Available time slots
                                </p>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <Label
                                            htmlFor="checkout-delivery-date"
                                            className="text-foreground text-xs font-extrabold uppercase"
                                        >
                                            Delivery date
                                        </Label>
                                        <div className="relative">
                                            <Input
                                                id="checkout-delivery-date"
                                                type="date"
                                                value={details.deliveryDate}
                                                onChange={(event) =>
                                                    onChange(
                                                        'deliveryDate',
                                                        event.target.value,
                                                    )
                                                }
                                                className={dateInputClass}
                                            />
                                            <CalendarDays
                                                className="text-muted-foreground pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2"
                                                aria-hidden
                                            />
                                        </div>
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label
                                            htmlFor="checkout-time-slot"
                                            className="text-foreground text-xs font-extrabold uppercase"
                                        >
                                            Time slot
                                        </Label>
                                        <Select
                                            value={details.timeSlotId}
                                            onValueChange={(value) =>
                                                onChange('timeSlotId', value)
                                            }
                                        >
                                            <SelectTrigger
                                                id="checkout-time-slot"
                                                className="h-11 min-h-11 w-full rounded-none border-none bg-background data-[size=default]:h-11"
                                            >
                                                <SelectValue placeholder="Select a time" />
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
                                    </div>
                                </div>
                            </div>
                        </>
                    ) : null}
                </CardContent>
            </Card>

            <Card className={sectionCardClass}>
                <CardHeader className={sectionHeaderClass}>
                    <CardTitle className="text-foreground text-lg font-extrabold sm:text-xl md:text-2xl">
                        5. Payment at delivery
                    </CardTitle>
                    <SectionBadge>No online card entry</SectionBadge>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div
                        role="radiogroup"
                        aria-label="Payment method"
                        className="grid gap-3 sm:grid-cols-2"
                    >
                        {paymentMethods.map((method) => {
                            const selected =
                                details.paymentMethodId === method.id;

                            return (
                                <button
                                    key={method.id}
                                    type="button"
                                    role="radio"
                                    aria-checked={selected}
                                    onClick={() =>
                                        onChange('paymentMethodId', method.id)
                                    }
                                    className={cn(
                                        'bg-background rounded-none p-3 text-left transition sm:p-4',
                                        selected &&
                                            'border-foreground ring-foreground/10 ring-1',
                                    )}
                                >
                                    <span className="flex items-start gap-3">
                                        <span
                                            className={cn(
                                                'bg-muted text-foreground flex size-9 shrink-0 items-center justify-center rounded-md text-xs font-bold',
                                                selected &&
                                                    'bg-foreground text-background',
                                            )}
                                            aria-hidden
                                        >
                                            {method.badge}
                                        </span>
                                        <span>
                                            <span className="text-foreground block text-sm font-bold">
                                                {method.title}
                                            </span>
                                            <span className="text-muted-foreground mt-1 block text-xs leading-relaxed">
                                                {method.description}
                                            </span>
                                        </span>
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <p className="text-muted-foreground text-xs leading-relaxed">
                        Simple payment: Have your chosen payment method ready
                        when the driver arrives.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
