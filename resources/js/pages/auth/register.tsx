import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

const authLabelClass =
    'text-muted-foreground text-xs font-semibold tracking-[0.12em] uppercase';

const authInputClass =
    'border-border h-11 rounded-none border-0 border-b bg-transparent px-0 shadow-none focus-visible:border-foreground focus-visible:ring-0';

const authButtonClass =
    'h-12 w-full rounded-md text-sm font-semibold tracking-wide uppercase shadow-[0_8px_24px_color-mix(in_oklab,var(--primary)_45%,transparent)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_color-mix(in_oklab,var(--primary)_55%,transparent)]';

const authLinkClass =
    'text-foreground font-medium underline-offset-4 hover:text-primary hover:underline';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                validationTimeout={500}
                className="flex flex-col gap-5 sm:gap-8"
            >
                {({ processing, errors, validate }) => (
                    <>
                        <div className="grid gap-4 sm:gap-6">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="name"
                                    className={authLabelClass}
                                >
                                    Full name
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                    className={authInputClass}
                                />
                                <InputError message={errors.name} />
                            </div>

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
                                    required
                                    tabIndex={2}
                                    autoComplete="tel"
                                    name="phone"
                                    placeholder="04XX XXX XXX"
                                    className={authInputClass}
                                    onBlur={() => validate('phone')}
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className={authLabelClass}
                                >
                                    Password
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                    className={cn(authInputClass, 'w-full')}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password_confirmation"
                                    className={authLabelClass}
                                >
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                    className={cn(authInputClass, 'w-full')}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className={authButtonClass}
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing ? <Spinner /> : null}
                                Create account
                            </Button>
                        </div>

                        <div className="text-muted-foreground text-center text-sm">
                            Already have an account?{' '}
                            <TextLink
                                href={login()}
                                className={authLinkClass}
                                tabIndex={6}
                            >
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create account',
    description:
        'Customers only — your name, phone number, and a password to get started.',
};
