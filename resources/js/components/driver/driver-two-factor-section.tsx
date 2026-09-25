import { Form, router, useHttp } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { confirm, confirmation } from '@/routes/password';
import { disable, enable } from '@/routes/two-factor';

function Field({
    icon: Icon,
    label,
    children,
}: {
    icon: typeof ShieldCheck;
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-start gap-3">
            <span className="bg-muted mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg">
                <Icon className="text-muted-foreground size-4" aria-hidden />
            </span>
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                <Label className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                    {label}
                </Label>
                {children}
            </div>
        </div>
    );
}

type DriverTwoFactorSectionProps = {
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    requiresConfirmation?: boolean;
};

export function DriverTwoFactorSection({
    canManageTwoFactor,
    twoFactorEnabled,
    requiresConfirmation = false,
}: DriverTwoFactorSectionProps) {
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
        <section className="border-border space-y-4 rounded-xl border p-4">
            <Field icon={ShieldCheck} label="Two-factor authentication">
                <Badge variant={twoFactorEnabled ? 'default' : 'secondary'}>
                    {twoFactorEnabled ? 'Enabled' : 'Disabled'}
                </Badge>
            </Field>

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
                                        size="lg"
                                        className="h-10 w-full sm:w-auto"
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
                    ) : null}
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
                                size="lg"
                                className="h-10 w-full sm:w-auto"
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
                                        size="lg"
                                        className="h-10 w-full sm:w-auto"
                                    >
                                        Enable
                                    </Button>
                                )}
                            </Form>
                        )
                    ) : null}
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
