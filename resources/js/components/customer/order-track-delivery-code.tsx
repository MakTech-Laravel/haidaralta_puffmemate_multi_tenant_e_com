import { Form } from '@inertiajs/react';
import { Eye, EyeOff, KeyRound } from 'lucide-react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Customer/OrderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';

type OrderTrackDeliveryCodeProps = {
    orderNumber: string;
    code: string | null;
};

export function OrderTrackDeliveryCode({
    orderNumber,
    code,
}: OrderTrackDeliveryCodeProps) {
    const [revealed, setRevealed] = useState(false);

    return (
        <div
            className="border-primary/20 bg-primary/5 flex flex-col gap-3 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between"
            data-test="customer-delivery-code"
        >
            <div className="flex min-w-0 items-start gap-3">
                <div className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-full">
                    <KeyRound className="size-5" />
                </div>
                <div className="min-w-0 space-y-1">
                    <p className="text-foreground text-sm font-semibold">
                        Delivery code
                    </p>
                    {code ? (
                        <p
                            className="text-foreground font-mono text-2xl font-bold tracking-[0.3em]"
                            aria-live="polite"
                        >
                            {revealed ? code : '••••••'}
                        </p>
                    ) : null}
                    <p className="text-muted-foreground text-sm">
                        {code
                            ? 'Give this code to the driver only after you have paid.'
                            : 'Send yourself a new code by SMS, email, and notification.'}
                    </p>
                </div>
            </div>
            <div className="flex shrink-0 flex-col gap-2 sm:items-end">
                <div className="flex gap-2">
                    {code ? (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="rounded-full"
                            onClick={() => setRevealed((current) => !current)}
                            data-test="customer-delivery-code-toggle"
                        >
                            {revealed ? (
                                <EyeOff data-icon="inline-start" />
                            ) : (
                                <Eye data-icon="inline-start" />
                            )}
                            {revealed ? 'Hide' : 'Show'}
                        </Button>
                    ) : null}
                    <Form
                        {...OrderController.resendPlacementCode.form(
                            orderNumber,
                        )}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setRevealed(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    size="sm"
                                    className="rounded-full"
                                    disabled={processing}
                                >
                                    {processing ? 'Sending…' : 'Resend code'}
                                </Button>
                                <InputError
                                    message={errors.code}
                                    className="mt-2"
                                />
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </div>
    );
}
