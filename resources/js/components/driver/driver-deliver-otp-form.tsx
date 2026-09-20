import { Form } from '@inertiajs/react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DriverDeliverOtpFormProps = {
    orderId: string;
    disabled?: boolean;
};

export function DriverDeliverOtpForm({
    orderId,
    disabled = false,
}: DriverDeliverOtpFormProps) {
    return (
        <section className="border-border bg-background space-y-4 border p-4 sm:p-5">
            <div className="space-y-1">
                <h2 className="text-foreground text-lg font-semibold tracking-tight">
                    Deliver with OTP
                </h2>
                <p className="text-muted-foreground text-base leading-relaxed">
                    Ask the customer for their delivery code, then submit to
                    mark this stop complete.
                </p>
            </div>

            {disabled ? (
                <p className="text-muted-foreground text-base">
                    This order is already marked delivered.
                </p>
            ) : (
                <Form
                    {...OrderController.deliver.form(orderId)}
                    className="space-y-4"
                    options={{ preserveScroll: true }}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="driver-deliver-otp">
                                    Customer OTP
                                </Label>
                                <Input
                                    id="driver-deliver-otp"
                                    name="otp"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    placeholder="Enter 4+ digit code"
                                    className="font-mono tracking-widest"
                                />
                                <InputError message={errors.otp} />
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="h-12 w-full"
                                disabled={processing}
                            >
                                {processing ? 'Submitting…' : 'Mark delivered'}
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </section>
    );
}
