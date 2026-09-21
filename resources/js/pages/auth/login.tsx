import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import PasskeyVerify from '@/components/passkey-verify';
import { PwaInstallMenuItem } from '@/components/pwa/pwa-install-menu-item';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const authLabelClass =
    'text-muted-foreground text-xs font-semibold tracking-[0.12em] uppercase';

const authInputClass =
    'border-border h-11 rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:border-foreground focus-visible:ring-0';

const authButtonClass =
    'h-12 w-full rounded-md text-sm font-semibold tracking-wide uppercase shadow-[0_8px_24px_color-mix(in_oklab,var(--primary)_45%,transparent)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_color-mix(in_oklab,var(--primary)_55%,transparent)]';

const authLinkClass =
    'text-foreground font-medium underline-offset-4 hover:text-primary hover:underline';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5 sm:gap-8"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4 sm:gap-6">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="phone"
                                    className={authLabelClass}
                                >
                                    Phone number
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="tel"
                                    placeholder="04XX XXX XXX"
                                    className={authInputClass}
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label
                                        htmlFor="password"
                                        className={authLabelClass}
                                    >
                                        Password
                                    </Label>
                                    {canResetPassword ? (
                                        <TextLink
                                            href={request()}
                                            className={cn(
                                                authLinkClass,
                                                'ml-auto text-sm',
                                            )}
                                            tabIndex={5}
                                        >
                                            Forgot password?
                                        </TextLink>
                                    ) : null}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                    className={cn(authInputClass, 'w-full')}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        value="1"
                                        tabIndex={3}
                                    />
                                    <Label
                                        htmlFor="remember"
                                        className="text-sm font-normal"
                                    >
                                        Remember me
                                    </Label>
                                </div>
                                <InputError message={errors.remember} />
                            </div>

                            <Button
                                type="submit"
                                className={authButtonClass}
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing ? <Spinner /> : null}
                                Log in
                            </Button>
                        </div>

                        <div className="text-muted-foreground text-center text-sm">
                            Don&apos;t have an account?{' '}
                            <TextLink
                                href={register()}
                                className={authLinkClass}
                                tabIndex={5}
                            >
                                Sign up
                            </TextLink>
                        </div>

                        <PwaInstallMenuItem className="text-muted-foreground justify-center text-sm" />
                    </>
                )}
            </Form>

            {status ? (
                <div className="text-primary mt-4 text-center text-sm font-medium">
                    {status}
                </div>
            ) : null}
        </>
    );
}

Login.layout = {
    title: 'Welcome back',
    description: 'Enter your phone number and password to continue.',
};
