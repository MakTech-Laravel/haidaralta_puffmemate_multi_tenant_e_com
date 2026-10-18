import { Head } from '@inertiajs/react';
import { AccountDeliveryAddressSection } from '@/components/customer/account-delivery-address-section';
import { AccountPasswordSection } from '@/components/customer/account-password-section';
import { AccountSettingSection } from '@/components/customer/account-setting-section';
import { AccountTwoFactorSection } from '@/components/customer/account-two-factor-section';
import { index as accountIndex } from '@/routes/customer/account';
import type { StorefrontSavedAddress } from '@/types/storefront';

type CustomerAccountPageProps = {
    mustVerifyPhone: boolean;
    passwordRules: string;
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
    deliveryAddress: StorefrontSavedAddress | null;
};

export default function CustomerAccountIndex({
    passwordRules,
    canManageTwoFactor,
    twoFactorEnabled,
    requiresConfirmation,
    deliveryAddress,
}: CustomerAccountPageProps) {
    return (
        <>
            <Head title="Account" />

            <div className="flex min-w-0 flex-col gap-5">
                <AccountSettingSection />
                <AccountDeliveryAddressSection
                    key={deliveryAddress ? 'saved' : 'empty'}
                    deliveryAddress={deliveryAddress}
                />
                <AccountPasswordSection passwordRules={passwordRules} />
                <AccountTwoFactorSection
                    canManageTwoFactor={canManageTwoFactor}
                    twoFactorEnabled={twoFactorEnabled}
                    requiresConfirmation={requiresConfirmation}
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
