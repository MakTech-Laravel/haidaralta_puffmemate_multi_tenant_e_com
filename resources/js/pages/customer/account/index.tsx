import { Head } from '@inertiajs/react';
import { AccountAddressSection } from '@/components/customer/account-address-section';
import { AccountPasswordSection } from '@/components/customer/account-password-section';
import { AccountSettingSection } from '@/components/customer/account-setting-section';
import { AccountTwoFactorSection } from '@/components/customer/account-two-factor-section';
import { index as accountIndex } from '@/routes/customer/account';

type CustomerAccountPageProps = {
    mustVerifyPhone: boolean;
    passwordRules: string;
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
};

export default function CustomerAccountIndex({
    passwordRules,
    canManageTwoFactor,
    twoFactorEnabled,
}: CustomerAccountPageProps) {
    return (
        <>
            <Head title="Account" />

            <div className="flex min-w-0 flex-col gap-5">
                <AccountSettingSection />
                <AccountPasswordSection passwordRules={passwordRules} />
                <AccountTwoFactorSection
                    canManageTwoFactor={canManageTwoFactor}
                    twoFactorEnabled={twoFactorEnabled}
                />
                <div className="grid min-w-0 gap-5 lg:grid-cols-2">
                    <AccountAddressSection
                        title="Billing Address"
                        idPrefix="billing"
                    />
                    <AccountAddressSection
                        title="Shipping Address"
                        idPrefix="shipping"
                    />
                </div>
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
