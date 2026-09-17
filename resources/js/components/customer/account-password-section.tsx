import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type AccountPasswordSectionProps = {
    passwordRules: string;
    className?: string;
};

export function AccountPasswordSection({
    passwordRules,
    className,
}: AccountPasswordSectionProps) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <section
            className={cn(
                'bg-muted w-full min-w-0 space-y-6 p-5 sm:p-6',
                className,
            )}
        >
            <h2 className={accountSectionTitleClass}>Change Password</h2>

            <Form
                {...SecurityController.update.form()}
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
                className="w-full space-y-4"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label
                                htmlFor="account-current-password"
                                className={accountLabelClass}
                            >
                                Current Password
                            </Label>
                            <PasswordInput
                                id="account-current-password"
                                ref={currentPasswordInput}
                                name="current_password"
                                autoComplete="current-password"
                                placeholder="Current Password"
                                className={cn(accountInputClass, 'w-full')}
                            />
                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="account-new-password"
                                className={accountLabelClass}
                            >
                                New Password
                            </Label>
                            <PasswordInput
                                id="account-new-password"
                                ref={passwordInput}
                                name="password"
                                autoComplete="new-password"
                                placeholder="8+ characters"
                                passwordrules={passwordRules}
                                className={cn(accountInputClass, 'w-full')}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="account-confirm-password"
                                className={accountLabelClass}
                            >
                                Confirm Password
                            </Label>
                            <PasswordInput
                                id="account-confirm-password"
                                name="password_confirmation"
                                autoComplete="new-password"
                                placeholder="Confirm Password"
                                passwordrules={passwordRules}
                                className={cn(accountInputClass, 'w-full')}
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className={accountButtonClass}
                        >
                            Change Password
                        </Button>
                    </>
                )}
            </Form>
        </section>
    );
}
