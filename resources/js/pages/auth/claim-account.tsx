import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/register/claim';

type Props = {
    phone?: string | null;
};

export default function ClaimAccount({ phone }: Props) {
    return (
        <>
            <Head title="Claim account" />

            <div className="space-y-6">
                <p className="text-muted-foreground text-sm">
                    This number already has orders. Enter the code sent to{' '}
                    {phone ?? 'your phone'} to claim the account. Those orders
                    stay hidden until this code is confirmed.
                </p>

                <Form {...store.form()} className="space-y-4">
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
                            >
                                {processing && <Spinner />}
                                Claim account
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ClaimAccount.layout = {
    title: 'Claim your account',
    description: 'Confirm the code sent to this phone number',
};
