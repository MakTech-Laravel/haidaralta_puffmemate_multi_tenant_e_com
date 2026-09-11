import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { send, verify } from '@/routes/phone/verification';

type Props = {
    phone?: string | null;
    status?: string;
};

export default function VerifyPhone({ phone, status }: Props) {
    return (
        <>
            <Head title="Verify phone" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <p className="text-muted-foreground text-sm">
                    We need to verify {phone ?? 'your phone number'} before you
                    continue.
                </p>

                <Form {...send.form()}>
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            className="w-full"
                            disabled={processing}
                            data-test="send-phone-verification-button"
                        >
                            {processing && <Spinner />}
                            Send verification code
                        </Button>
                    )}
                </Form>

                <Form {...verify.form()} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="code">Verification code</Label>
                                <Input
                                    id="code"
                                    type="text"
                                    name="code"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    required
                                    placeholder="6-digit code"
                                />
                                <InputError message={errors.code} />
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                                data-test="verify-phone-button"
                            >
                                {processing && <Spinner />}
                                Verify phone
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

VerifyPhone.layout = {
    title: 'Verify your phone',
    description: 'Enter the code we send to your phone number',
};
