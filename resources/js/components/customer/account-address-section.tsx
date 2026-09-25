import { Form } from '@inertiajs/react';
import { useState } from 'react';
import AccountController from '@/actions/App/Http/Controllers/Customer/AccountController';
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import {
    AccountRegionSelect,
    type AccountRegionOption,
} from '@/components/customer/account-region-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type CustomerBillingAddress = {
    name: string | null;
    address: string | null;
    region: string | null;
    city: string | null;
    zip: string | null;
    email: string | null;
    phone: string | null;
};

type AccountAddressSectionProps = {
    title: string;
    idPrefix: string;
    billingAddress?: CustomerBillingAddress | null;
    regions?: readonly AccountRegionOption[];
    className?: string;
};

export function AccountAddressSection({
    title,
    idPrefix,
    billingAddress = null,
    regions,
    className,
}: AccountAddressSectionProps) {
    const [region, setRegion] = useState(billingAddress?.region ?? '');

    return (
        <section
            className={cn(
                'bg-muted flex w-full min-w-0 flex-col space-y-5 p-5 sm:p-6',
                className,
            )}
        >
            <h2 className={accountSectionTitleClass}>{title}</h2>

            <Form
                {...AccountController.updateBillingAddress.form()}
                options={{ preserveScroll: true }}
                className="flex min-w-0 flex-1 flex-col space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label
                                htmlFor={`${idPrefix}-full-name`}
                                className={accountLabelClass}
                            >
                                Full Name
                            </Label>
                            <Input
                                id={`${idPrefix}-full-name`}
                                name="name"
                                placeholder="Full Name"
                                defaultValue={billingAddress?.name ?? ''}
                                autoComplete="name"
                                className={accountInputClass}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor={`${idPrefix}-address`}
                                className={accountLabelClass}
                            >
                                Address
                            </Label>
                            <Input
                                id={`${idPrefix}-address`}
                                name="address"
                                placeholder="Address"
                                defaultValue={billingAddress?.address ?? ''}
                                className={accountInputClass}
                            />
                            <InputError message={errors.address} />
                        </div>

                        <AccountRegionSelect
                            id={`${idPrefix}-region`}
                            name="region"
                            value={region}
                            onValueChange={setRegion}
                            error={errors.region}
                            regions={regions}
                        />

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${idPrefix}-city`}
                                    className={accountLabelClass}
                                >
                                    City
                                </Label>
                                <Input
                                    id={`${idPrefix}-city`}
                                    name="city"
                                    placeholder="City"
                                    defaultValue={billingAddress?.city ?? ''}
                                    className={accountInputClass}
                                />
                                <InputError message={errors.city} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`${idPrefix}-zip`}
                                    className={accountLabelClass}
                                >
                                    Zip Code
                                </Label>
                                <Input
                                    id={`${idPrefix}-zip`}
                                    name="zip"
                                    placeholder="Zip Code"
                                    defaultValue={billingAddress?.zip ?? ''}
                                    className={accountInputClass}
                                />
                                <InputError message={errors.zip} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor={`${idPrefix}-email`}
                                className={accountLabelClass}
                            >
                                Email
                            </Label>
                            <Input
                                id={`${idPrefix}-email`}
                                type="email"
                                name="email"
                                placeholder="Email"
                                defaultValue={billingAddress?.email ?? ''}
                                className={accountInputClass}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor={`${idPrefix}-phone`}
                                className={accountLabelClass}
                            >
                                Phone Number
                            </Label>
                            <Input
                                id={`${idPrefix}-phone`}
                                type="tel"
                                name="phone"
                                placeholder="Phone Number"
                                defaultValue={billingAddress?.phone ?? ''}
                                className={accountInputClass}
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className={cn(accountButtonClass, 'mt-auto w-fit')}
                        >
                            Save Changes
                        </Button>
                    </>
                )}
            </Form>
        </section>
    );
}
