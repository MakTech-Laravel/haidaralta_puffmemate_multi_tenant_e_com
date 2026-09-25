import { Form, router, useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
    accountButtonClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { cn } from '@/lib/utils';
import { confirm, confirmation } from '@/routes/password';
import { disable, enable } from '@/routes/two-factor';

type AccountTwoFactorSectionProps = {
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    requiresConfirmation?: boolean;
    className?: string;
};

export function AccountTwoFactorSection({
    canManageTwoFactor,
    twoFactorEnabled,
    requiresConfirmation = false,
    className,
}: AccountTwoFactorSectionProps) {
    const { submit } = useHttp();
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes: loadRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState(false);
    const prevTwoFactorEnabled = useRef(twoFactorEnabled);

    const fetchRecoveryCodes = useCallback(async (): Promise<boolean> => {
        try {
            const { confirmed } = (await submit(confirmation())) as {
                confirmed: boolean;
            };

            if (!confirmed) {
                router.visit(confirm.url());

                return false;
            }

            return await loadRecoveryCodes();
        } catch {
            return false;
        }
    }, [submit, loadRecoveryCodes]);

    useEffect(() => {
        if (prevTwoFactorEnabled.current && !twoFactorEnabled) {
            clearTwoFactorAuthData();
        }

        prevTwoFactorEnabled.current = twoFactorEnabled;
    }, [twoFactorEnabled, clearTwoFactorAuthData]);

    return (
        <section
            className={cn(
                'bg-muted w-full min-w-0 space-y-4 p-5 sm:p-6',
                className,
            )}
        >
            <h2 className={accountSectionTitleClass}>
                Two Factor Authentication
            </h2>

            {twoFactorEnabled ? (
                <div className="flex flex-col items-start space-y-4">
                    <p className="text-foreground text-sm">
                        You will be prompted for a secure, random pin during
                        login, which you can retrieve from the TOTP-supported
                        application on your phone.
                    </p>

                    {canManageTwoFactor ? (
                        <>
                            <Form {...disable.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className={accountButtonClass}
                                    >
                                        Disable
                                    </Button>
                                )}
                            </Form>

                            <TwoFactorRecoveryCodes
                                autoFetch={false}
                                recoveryCodesList={recoveryCodesList}
                                fetchRecoveryCodes={fetchRecoveryCodes}
                                errors={errors}
                            />
                        </>
                    ) : (
                        <Button type="button" className={accountButtonClass} disabled>
                            Enabled
                        </Button>
                    )}
                </div>
            ) : (
                <div className="flex flex-col items-start space-y-4">
                    <p className="text-foreground text-sm">
                        When you enable two-factor authentication, you will be
                        prompted for a secure pin during login. This pin can be
                        retrieved from a TOTP-supported application on your
                        phone.
                    </p>

                    {canManageTwoFactor ? (
                        hasSetupData ? (
                            <Button
                                type="button"
                                className={accountButtonClass}
                                onClick={() => setShowSetupModal(true)}
                            >
                                Continue setup
                            </Button>
                        ) : (
                            <Form
                                {...enable.form()}
                                onSuccess={() => setShowSetupModal(true)}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className={accountButtonClass}
                                    >
                                        Enable
                                    </Button>
                                )}
                            </Form>
                        )
                    ) : (
                        <Button type="button" className={accountButtonClass} disabled>
                            Enable
                        </Button>
                    )}
                </div>
            )}

            {canManageTwoFactor ? (
                <TwoFactorSetupModal
                    isOpen={showSetupModal}
                    onClose={() => setShowSetupModal(false)}
                    requiresConfirmation={requiresConfirmation}
                    twoFactorEnabled={twoFactorEnabled}
                    qrCodeSvg={qrCodeSvg}
                    manualSetupKey={manualSetupKey}
                    clearSetupData={clearSetupData}
                    fetchSetupData={fetchSetupData}
                    errors={errors}
                />
            ) : null}
        </section>
    );
}
