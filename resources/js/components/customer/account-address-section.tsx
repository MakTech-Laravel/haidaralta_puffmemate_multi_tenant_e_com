import { useState } from 'react';
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import { AccountRegionSelect } from '@/components/customer/account-region-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type AccountAddressSectionProps = {
    title: string;
    idPrefix: string;
    className?: string;
};

export function AccountAddressSection({
    title,
    idPrefix,
    className,
}: AccountAddressSectionProps) {
    const [region, setRegion] = useState('');

    return (
        <section
            className={cn(
                'bg-muted flex w-full min-w-0 flex-col space-y-5 p-5 sm:p-6',
                className,
            )}
        >
            <h2 className={accountSectionTitleClass}>{title}</h2>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label
                        htmlFor={`${idPrefix}-first-name`}
                        className={accountLabelClass}
                    >
                        First Name
                    </Label>
                    <Input
                        id={`${idPrefix}-first-name`}
                        placeholder="First Name"
                        className={accountInputClass}
                    />
                </div>
                <div className="grid gap-2">
                    <Label
                        htmlFor={`${idPrefix}-last-name`}
                        className={accountLabelClass}
                    >
                        Last Name
                    </Label>
                    <Input
                        id={`${idPrefix}-last-name`}
                        placeholder="Last Name"
                        className={accountInputClass}
                    />
                </div>
            </div>

            <div className="grid gap-2">
                <Label
                    htmlFor={`${idPrefix}-company`}
                    className={accountLabelClass}
                >
                    Company Name (Optional)
                </Label>
                <Input
                    id={`${idPrefix}-company`}
                    placeholder="Company Name"
                    className={accountInputClass}
                />
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
                    placeholder="Address"
                    className={accountInputClass}
                />
            </div>

            <AccountRegionSelect
                id={`${idPrefix}-region`}
                value={region}
                onValueChange={setRegion}
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
                        placeholder="City"
                        className={accountInputClass}
                    />
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
                        placeholder="Zip Code"
                        className={accountInputClass}
                    />
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
                    placeholder="Email"
                    className={accountInputClass}
                />
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
                    placeholder="Phone Number"
                    className={accountInputClass}
                />
            </div>

            <Button type="button" className={cn(accountButtonClass, 'mt-auto w-fit')}>
                Save Changes
            </Button>
        </section>
    );
}
