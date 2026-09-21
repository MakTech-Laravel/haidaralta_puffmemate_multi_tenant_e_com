import { Head } from '@inertiajs/react';
import { KeyRound, Lock, Phone, Shield, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
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
    twoFactorEnabled,
    passkeyCount,
}: {
    phone: string;
    phoneVerified: boolean;
    twoFactorEnabled: boolean;
    passkeyCount: number;
}) {
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
                            Review how you sign in. Nothing here can be changed
                            yet.
                        </p>
                    </div>
                </section>

                <section className="border-border space-y-4 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Password
                    </h2>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            icon={Lock}
                            label="Current password"
                            htmlFor="current_password"
                        >
                            <PasswordInput
                                id="current_password"
                                autoComplete="off"
                                placeholder="Current password"
                                defaultValue=""
                                
                            />
                        </Field>
                        <Field
                            icon={KeyRound}
                            label="New password"
                            htmlFor="password"
                        >
                            <PasswordInput
                                id="password"
                                autoComplete="off"
                                placeholder="New password"
                                defaultValue=""
                                
                            />
                        </Field>
                        <Field
                            icon={ShieldCheck}
                            label="Confirm password"
                            htmlFor="password_confirmation"
                        >
                            <PasswordInput
                                id="password_confirmation"
                                autoComplete="off"
                                placeholder="Confirm password"
                                defaultValue=""
                                
                            />
                        </Field>
                    </div>
                    <Button
                        type="button"
                        size="lg"
                        className="h-10 w-full sm:w-auto cursor-pointer"
                    >
                        Update password
                    </Button>
                </section>

                <section className="border-border rounded-xl border p-4">
                    <Field icon={ShieldCheck} label="Two-factor authentication">
                        <Badge
                            variant={twoFactorEnabled ? 'default' : 'secondary'}
                        >
                            {twoFactorEnabled ? 'Enabled' : 'Disabled'}
                        </Badge>
                    </Field>
                </section>

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
