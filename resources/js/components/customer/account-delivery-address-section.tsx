import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AccountController from '@/actions/App/Http/Controllers/Customer/AccountController';
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import InputError from '@/components/input-error';
import { CheckoutMapDialog } from '@/components/storefront/checkout/checkout-map-dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';
import type { StorefrontSavedAddress } from '@/types/storefront';

type AccountDeliveryAddressSectionProps = {
    deliveryAddress: StorefrontSavedAddress | null;
    className?: string;
};

export function AccountDeliveryAddressSection({
    deliveryAddress,
    className,
}: AccountDeliveryAddressSectionProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [removing, setRemoving] = useState(false);
    const form = useForm({
        name: deliveryAddress?.name ?? auth.user?.name ?? '',
        mobile: deliveryAddress?.mobile ?? auth.user?.phone ?? '',
        address: deliveryAddress?.address ?? '',
        city: deliveryAddress?.city ?? '',
        postal_code: deliveryAddress?.postal_code ?? '',
        latitude: deliveryAddress?.latitude ?? '',
        longitude: deliveryAddress?.longitude ?? '',
    });
    const pinned = form.data.latitude !== '' && form.data.longitude !== '';

    const onRemove = () => {
        router.delete(AccountController.destroyDeliveryAddress.url(), {
            preserveScroll: true,
            onStart: () => setRemoving(true),
            onFinish: () => setRemoving(false),
            onSuccess: () => {
                form.setDefaults({
                    name: auth.user?.name ?? '',
                    mobile: auth.user?.phone ?? '',
                    address: '',
                    city: '',
                    postal_code: '',
                    latitude: '',
                    longitude: '',
                });
                form.reset();
            },
        });
    };

    return (
        <section
            className={cn(
                'bg-muted w-full min-w-0 space-y-5 p-5 sm:p-6',
                className,
            )}
        >
            <div className="space-y-1">
                <h2 className={accountSectionTitleClass}>
                    Default Delivery Address
                </h2>
                <p className="text-muted-foreground text-sm">
                    Checkout fills in this address automatically. You can keep
                    one saved address.
                </p>
            </div>

            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.put(AccountController.updateDeliveryAddress.url(), {
                        preserveScroll: true,
                        onSuccess: () => form.setDefaults(),
                    });
                }}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label
                            htmlFor="delivery-name"
                            className={accountLabelClass}
                        >
                            Receiver Name
                        </Label>
                        <Input
                            id="delivery-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            autoComplete="name"
                            placeholder="Full name"
                            className={accountInputClass}
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label
                            htmlFor="delivery-mobile"
                            className={accountLabelClass}
                        >
                            Mobile Number
                        </Label>
                        <Input
                            id="delivery-mobile"
                            type="tel"
                            value={form.data.mobile}
                            onChange={(event) =>
                                form.setData('mobile', event.target.value)
                            }
                            autoComplete="tel"
                            placeholder="0400 000 000"
                            className={accountInputClass}
                        />
                        <InputError message={form.errors.mobile} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="delivery-address"
                        className={accountLabelClass}
                    >
                        Address
                    </Label>
                    <div className="flex items-start gap-2">
                        <Input
                            id="delivery-address"
                            value={form.data.address}
                            onChange={(event) =>
                                form.setData('address', event.target.value)
                            }
                            autoComplete="street-address"
                            placeholder="Street address"
                            className={cn(accountInputClass, 'min-w-0 flex-1')}
                        />
                        <CheckoutMapDialog
                            latitude={form.data.latitude}
                            longitude={form.data.longitude}
                            onSelect={(pin) =>
                                form.setData((current) => ({
                                    ...current,
                                    latitude: pin.latitude,
                                    longitude: pin.longitude,
                                    address: pin.address || current.address,
                                    city: pin.city || current.city,
                                    postal_code:
                                        pin.postalCode || current.postal_code,
                                }))
                            }
                        />
                    </div>
                    <InputError message={form.errors.address} />
                </div>

                {pinned ? null : (
                    <Alert variant="destructive">
                        <AlertDescription>
                            Pin the location on the map so checkout can find
                            the nearest branch and delivery fee.
                        </AlertDescription>
                    </Alert>
                )}
                <InputError message={form.errors.latitude} />

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label
                            htmlFor="delivery-city"
                            className={accountLabelClass}
                        >
                            City
                        </Label>
                        <Input
                            id="delivery-city"
                            value={form.data.city}
                            onChange={(event) =>
                                form.setData('city', event.target.value)
                            }
                            autoComplete="address-level2"
                            placeholder="Suburb or city"
                            className={accountInputClass}
                        />
                        <InputError message={form.errors.city} />
                    </div>
                    <div className="grid gap-2">
                        <Label
                            htmlFor="delivery-postal-code"
                            className={accountLabelClass}
                        >
                            Postal Code
                        </Label>
                        <Input
                            id="delivery-postal-code"
                            value={form.data.postal_code}
                            onChange={(event) =>
                                form.setData('postal_code', event.target.value)
                            }
                            autoComplete="postal-code"
                            placeholder="2000"
                            className={accountInputClass}
                        />
                        <InputError message={form.errors.postal_code} />
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-3 pt-2">
                    <Button
                        type="submit"
                        disabled={form.processing || !pinned}
                        className={accountButtonClass}
                    >
                        {deliveryAddress ? 'Update Address' : 'Save Address'}
                    </Button>
                    {deliveryAddress ? (
                        <Button
                            type="button"
                            variant="outline"
                            disabled={removing}
                            onClick={onRemove}
                            className={accountButtonClass}
                        >
                            Remove
                        </Button>
                    ) : null}
                </div>
            </form>
        </section>
    );
}
