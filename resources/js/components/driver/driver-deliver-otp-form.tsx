import { Form } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { KeyRound } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

const DELIVERY_CODE_LENGTH = 6;

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
    const [code, setCode] = useState('');
    const submit = form ?? OrderController.deliver.form(orderId);

    const changeOpen = (next: boolean) => {
        setOpen(next);

        if (!next) {
            setCode('');
        }
    };

    return (
        <Sheet open={open} onOpenChange={changeOpen}>
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
            <SheetContent
                side="bottom"
                className="max-h-[85svh] gap-0 overflow-y-auto rounded-t-3xl sm:mx-auto sm:max-w-lg"
            >
                <div className="bg-muted mx-auto mt-2.5 h-1.5 w-10 shrink-0 rounded-full" />
                <SheetHeader className="items-center text-center">
                    <div className="bg-primary/10 text-primary mb-1 flex size-12 items-center justify-center rounded-full">
                        <KeyRound className="size-5" />
                    </div>
                    <SheetTitle>Complete delivery</SheetTitle>
                    <SheetDescription>
                        Collect the payment, then enter the 6-digit code from
                        the customer.
                    </SheetDescription>
                </SheetHeader>
                {disabled ? (
                    <p className="text-muted-foreground px-4 pb-6 text-center text-sm">
                        This order is already marked delivered.
                    </p>
                ) : (
                    <Form
                        {...submit}
                        className="space-y-5 px-4 pb-6"
                        options={{ preserveScroll: true }}
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="flex flex-col items-center gap-2">
                                    <InputOTP
                                        id={`deliver-otp-${orderId}`}
                                        name="otp"
                                        aria-label="Customer code"
                                        maxLength={DELIVERY_CODE_LENGTH}
                                        value={code}
                                        onChange={setCode}
                                        pattern={REGEXP_ONLY_DIGITS}
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        disabled={processing}
                                        autoFocus
                                    >
                                        <InputOTPGroup className="gap-2">
                                            {Array.from(
                                                {
                                                    length: DELIVERY_CODE_LENGTH,
                                                },
                                                (_, index) => (
                                                    <InputOTPSlot
                                                        key={index}
                                                        index={index}
                                                        className="size-12 rounded-xl border text-lg font-semibold shadow-none first:rounded-xl last:rounded-xl"
                                                    />
                                                ),
                                            )}
                                        </InputOTPGroup>
                                    </InputOTP>
                                    <InputError message={errors.otp} />
                                </div>
                                <Button
                                    type="submit"
                                    size="lg"
                                    className="h-12 w-full rounded-full"
                                    disabled={
                                        processing ||
                                        code.length < DELIVERY_CODE_LENGTH
                                    }
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
