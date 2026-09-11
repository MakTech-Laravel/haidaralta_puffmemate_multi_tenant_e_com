import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store as storeOtp } from '@/routes/password/otp';

type Props = {
    phone: string;
    status?: string;
};

export default function VerifyPasswordOtp({ phone, status }: Props) {
    return (
        <>
            <Head title="Verify code" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <Form {...storeOtp.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <input type="hidden" name="phone" value={phone} />

                        <div className="grid gap-2">
                            <Label htmlFor="phone_display">Phone number</Label>
                            <Input
                                id="phone_display"
                                type="tel"
                                value={phone}
                                readOnly
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code">Verification code</Label>
                            <Input
                                id="code"
                                type="text"
                                name="code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                required
                                autoFocus
                                placeholder="6-digit code"
                            />
                            <InputError message={errors.code} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="verify-password-otp-button"
                        >
                            {processing && <Spinner />}
                            Verify code
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

VerifyPasswordOtp.layout = {
    title: 'Enter verification code',
    description: 'Enter the 6-digit code sent to your phone',
};
