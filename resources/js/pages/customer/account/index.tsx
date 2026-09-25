import { Head } from '@inertiajs/react';
import { AccountAddressSection } from '@/components/customer/account-address-section';
import type { CustomerBillingAddress } from '@/components/customer/account-address-section';
import { AccountPasswordSection } from '@/components/customer/account-password-section';
import type { AccountRegionOption } from '@/components/customer/account-region-select';
import { AccountSettingSection } from '@/components/customer/account-setting-section';
import type { CustomerShippingAddress } from '@/components/customer/account-setting-section';
import { AccountTwoFactorSection } from '@/components/customer/account-two-factor-section';
import { index as accountIndex } from '@/routes/customer/account';

type CustomerAccountPageProps = {
    mustVerifyPhone: boolean;
    passwordRules: string;
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    shippingAddress: CustomerShippingAddress | null;
    billingAddress: CustomerBillingAddress | null;
    regions: AccountRegionOption[];
};

export default function CustomerAccountIndex({
    passwordRules,
    canManageTwoFactor,
    twoFactorEnabled,
    shippingAddress,
    billingAddress,
    regions,
}: CustomerAccountPageProps) {
    return (
        <>
            <Head title="Account" />

            <div className="flex min-w-0 flex-col gap-5">
                <AccountSettingSection
                    shippingAddress={shippingAddress}
                    regions={regions}
                />
                <AccountPasswordSection passwordRules={passwordRules} />
                <AccountTwoFactorSection
                    canManageTwoFactor={canManageTwoFactor}
                    twoFactorEnabled={twoFactorEnabled}
                />
                <AccountAddressSection
                    title="Billing Address"
                    idPrefix="billing"
                    billingAddress={billingAddress}
                    regions={regions}
                />
            </div>
        </>
    );
}

CustomerAccountIndex.layout = {
    breadcrumbs: [
        {
            title: 'My Account',
            href: accountIndex.url(),
        },
    ],
};
