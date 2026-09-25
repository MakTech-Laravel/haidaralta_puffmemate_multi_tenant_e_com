import { Form, Head } from '@inertiajs/react';
import { KeyRound, Lock, Phone, Shield, ShieldCheck } from 'lucide-react';
import { useRef, type ReactNode } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Driver/SecurityController';
import { DriverTwoFactorSection } from '@/components/driver/driver-two-factor-section';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index as securityIndex } from '@/routes/driver/security';

function Field({
    icon: Icon,
    label,
    htmlFor,
    children,
}: {
    icon: typeof Lock;
    label: string;
    htmlFor?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-start gap-3">
            <span className="bg-muted mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg">
                <Icon className="text-muted-foreground size-4" aria-hidden />
            </span>
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                {htmlFor ? (
                    <Label
                        htmlFor={htmlFor}
                        className="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                    >
                        {label}
                    </Label>
                ) : (
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        {label}
                    </p>
                )}
                {children}
            </div>
        </div>
    );
}

export default function DriverSecurityIndex({
    phone,
    phoneVerified,
    passwordRules,
    canManageTwoFactor,
    twoFactorEnabled,
    requiresConfirmation,
    passkeyCount,
}: {
    phone: string;
    phoneVerified: boolean;
    passwordRules: string;
    canManageTwoFactor: boolean;
    twoFactorEnabled: boolean;
    requiresConfirmation: boolean;
    passkeyCount: number;
}) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Security" />

            <div className="flex w-full flex-col gap-5">
                <section className="bg-accent flex items-center gap-3 rounded-xl p-4">
                    <span className="bg-background flex size-12 shrink-0 items-center justify-center rounded-lg">
                        <Shield className="size-6" aria-hidden />
                    </span>
                    <div className="min-w-0 space-y-1">
                        <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                            Security
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Update your password and manage two-factor
                            authentication.
                        </p>
                    </div>
                </section>

                <section className="border-border space-y-4 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Password
                    </h2>
                    <Form
                        {...SecurityController.updatePassword.form()}
                        options={{ preserveScroll: true }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        icon={Lock}
                                        label="Current password"
                                        htmlFor="current_password"
                                    >
                                        <PasswordInput
                                            id="current_password"
                                            ref={currentPasswordInput}
                                            name="current_password"
                                            autoComplete="current-password"
                                            placeholder="Current password"
                                        />
                                        <InputError
                                            message={errors.current_password}
                                        />
                                    </Field>
                                    <Field
                                        icon={KeyRound}
                                        label="New password"
                                        htmlFor="password"
                                    >
                                        <PasswordInput
                                            id="password"
                                            ref={passwordInput}
                                            name="password"
                                            autoComplete="new-password"
                                            placeholder="New password"
                                            passwordrules={passwordRules}
                                        />
                                        <InputError message={errors.password} />
                                    </Field>
                                    <Field
                                        icon={ShieldCheck}
                                        label="Confirm password"
                                        htmlFor="password_confirmation"
                                    >
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            placeholder="Confirm password"
                                            passwordrules={passwordRules}
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </Field>
                                </div>
                                <Button
                                    type="submit"
                                    size="lg"
                                    disabled={processing}
                                    className="h-10 w-full sm:w-auto"
                                >
                                    Update password
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <DriverTwoFactorSection
                    canManageTwoFactor={canManageTwoFactor}
                    twoFactorEnabled={twoFactorEnabled}
                    requiresConfirmation={requiresConfirmation}
                />

                <section className="border-border rounded-xl border p-4">
                    <Field icon={KeyRound} label="Passkeys">
                        <p className="text-foreground text-sm font-medium">
                            {passkeyCount > 0
                                ? `${passkeyCount} registered`
                                : 'None registered'}
                        </p>
                    </Field>
                </section>

                <section className="border-border rounded-xl border p-4">
                    <Field icon={Phone} label="Phone">
                        <div className="flex flex-wrap items-center gap-2">
                            <p className="text-foreground text-sm font-medium wrap-break-word">
                                {phone}
                            </p>
                            <Badge
                                variant={phoneVerified ? 'default' : 'secondary'}
                            >
                                {phoneVerified ? 'Verified' : 'Unverified'}
                            </Badge>
                        </div>
                    </Field>
                </section>
            </div>
        </>
    );
}

DriverSecurityIndex.layout = {
    breadcrumbs: [
        {
            title: 'Security',
            href: securityIndex.url(),
        },
    ],
};
