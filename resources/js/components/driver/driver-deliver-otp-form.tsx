import { Form } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

type DriverDeliverOtpFormProps = {
    orderId: string;
    disabled?: boolean;
    label?: string;
    icon?: ReactNode;
    form?: {
        action: string;
        method: 'post';
    };
};

export function DriverDeliverOtpForm({
    orderId,
    disabled = false,
    label = 'Complete',
    icon = null,
    form,
}: DriverDeliverOtpFormProps) {
    const [open, setOpen] = useState(false);
    const submit = form ?? OrderController.deliver.form(orderId);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    type="button"
                    size="lg"
                    className="h-12 w-full"
                    disabled={disabled}
                    data-test="order-complete"
                >
                    {icon}
                    {label}
                </Button>
            </SheetTrigger>
            <SheetContent>
                <SheetHeader>
                    <SheetTitle>Complete delivery</SheetTitle>
                    <SheetDescription>
                        Collect the payment before you confirm the code. A
                        valid code marks the order delivered and the payment
                        collected.
                    </SheetDescription>
                </SheetHeader>
                {disabled ? (
                    <p className="text-muted-foreground px-4 text-sm">
                        This order is already marked delivered.
                    </p>
                ) : (
                    <Form
                        {...submit}
                        className="space-y-4 px-4"
                        options={{ preserveScroll: true }}
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor={`deliver-otp-${orderId}`}>
                                        Customer OTP
                                    </Label>
                                    <Input
                                        id={`deliver-otp-${orderId}`}
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
                                    {processing
                                        ? 'Submitting…'
                                        : 'Confirm and collect'}
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </SheetContent>
        </Sheet>
    );
}
