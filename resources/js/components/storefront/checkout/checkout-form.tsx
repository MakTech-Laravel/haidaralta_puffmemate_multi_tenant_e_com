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
    onChange: <K extends keyof CheckoutDetails>(
        key: K,
        value: CheckoutDetails[K],
    ) => void;
    className?: string;
};

const sectionCardClass =
    'bg-accent gap-3 rounded-none border-none p-3 shadow-none sm:gap-4 sm:p-5';

export function CheckoutForm({
    details,
    addresses,
    deliveryMethods,
    paymentMethods,
    timeSlots,
    onChange,
    className,
}: CheckoutFormProps) {
    const selectedMethod = deliveryMethods.find(
        (method) => method.id === details.deliveryMethodId,
    );
    const showTimeSlots = selectedMethod?.requiresSlot ?? false;

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

    return (
        <div className={cn('flex flex-col gap-3 sm:gap-4', className)}>
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
                                <SelectTrigger className="bg-background h-11 rounded-none border-none">
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
                                First name
                            </Label>
                            <Input
                                id="checkout-first-name"
                                value={details.firstName}
                                onChange={(event) =>
                                    onChange('firstName', event.target.value)
                                }
                                className="bg-background h-11 rounded-none border-none"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="checkout-last-name">
                                Last name
                            </Label>
                            <Input
                                id="checkout-last-name"
                                value={details.lastName}
                                onChange={(event) =>
                                    onChange('lastName', event.target.value)
                                }
                                className="bg-background h-11 rounded-none border-none"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="checkout-mobile">Mobile number</Label>
                        <Input
                            id="checkout-mobile"
                            type="tel"
                            value={details.mobile}
                            onChange={(event) =>
                                onChange('mobile', event.target.value)
                            }
                            className="bg-background h-11 rounded-none border-none"
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
                            <Label htmlFor="checkout-address">Address</Label>
                            <Input
                                id="checkout-address"
                                value={details.address}
                                onChange={(event) =>
                                    onChange('address', event.target.value)
                                }
                                className="bg-background h-11 rounded-none border-none"
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
                            <Label htmlFor="checkout-city">City</Label>
                            <Input
                                id="checkout-city"
                                value={details.city}
                                onChange={(event) =>
                                    onChange('city', event.target.value)
                                }
                                className="bg-background h-11 rounded-none border-none"
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
                                className="bg-background h-11 rounded-none border-none"
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
                <CardHeader>
                    <CardTitle className="text-foreground text-lg font-extrabold">
                        3. Delivery method
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4 px-0">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {deliveryMethods.map((method) => (
                            <button
                                key={method.id}
                                type="button"
                                onClick={() =>
                                    onChange('deliveryMethodId', method.id)
                                }
                                className={cn(
                                    'bg-background rounded-none p-4 text-left',
                                    details.deliveryMethodId === method.id
                                        ? 'ring-primary ring-2'
                                        : '',
                                )}
                            >
                                <p className="font-semibold">{method.label}</p>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {method.description}
                                </p>
                                <p className="mt-2 text-sm font-medium">
                                    {method.formattedFee}
                                </p>
                            </button>
                        ))}
                    </div>
                    {showTimeSlots ? (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label>Delivery date</Label>
                                <DatePicker
                                    value={details.deliveryDate || null}
                                    onChange={(value) =>
                                        onChange('deliveryDate', value)
                                    }
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label>Time slot</Label>
                                <Select
                                    value={details.timeSlotId}
                                    onValueChange={(value) =>
                                        onChange('timeSlotId', value)
                                    }
                                >
                                    <SelectTrigger className="bg-background h-11 rounded-none border-none">
                                        <SelectValue placeholder="Select a slot" />
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
                <CardHeader>
                    <CardTitle className="text-foreground text-lg font-extrabold">
                        4. Payment at delivery
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 px-0">
                    {paymentMethods.map((method) => (
                        <label
                            key={method.id}
                            className={cn(
                                'bg-background flex cursor-pointer items-start gap-3 p-4',
                                details.paymentMethodId === method.id
                                    ? 'ring-primary ring-2'
                                    : '',
                            )}
                        >
                            <input
                                type="radio"
                                name="payment_method"
                                checked={details.paymentMethodId === method.id}
                                onChange={() =>
                                    onChange('paymentMethodId', method.id)
                                }
                            />
                            <span>
                                <span className="block font-semibold">
                                    {method.label}
                                </span>
                                <span className="text-muted-foreground text-sm">
                                    {method.description}
                                </span>
                            </span>
                        </label>
                    ))}
                </CardContent>
            </Card>
        </div>
    );
}
