import { Form } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { DriverDeliverOtpForm } from '@/components/driver/driver-deliver-otp-form';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Option = {
    value: string;
    label: string;
};

type FormAction = {
    action: string;
    method: 'post';
};

type OrderHandoverActionsProps = {
    orderId: string | number;
    deliverForm: FormAction;
    failForm: FormAction;
    resendForm?: FormAction | null;
    withoutCodeForm?: FormAction | null;
    canComplete: boolean;
    canReportUnavailable: boolean;
    canCompleteWithoutCode?: boolean;
    failureReasons: Option[];
    verificationMethods?: Option[];
};

export function OrderHandoverActions({
    orderId,
    deliverForm,
    failForm,
    resendForm = null,
    withoutCodeForm = null,
    canComplete,
    canReportUnavailable,
    canCompleteWithoutCode = false,
    failureReasons,
    verificationMethods = [],
}: OrderHandoverActionsProps) {
    const showWithoutCode =
        canCompleteWithoutCode &&
        withoutCodeForm !== null &&
        verificationMethods.length > 0;

    if (!canComplete && !canReportUnavailable && !showWithoutCode) {
        return null;
    }

    return (
        <div className="space-y-3">
            {canComplete ? (
                <DriverDeliverOtpForm
                    orderId={String(orderId)}
                    form={deliverForm}
                    resendForm={resendForm}
                />
            ) : null}
            {showWithoutCode && withoutCodeForm ? (
                <Form
                    {...withoutCodeForm}
                    className="space-y-3 rounded-xl border border-amber-500/40 bg-amber-500/5 p-4"
                    options={{ preserveScroll: true }}
                    data-test="order-complete-without-code"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex items-start gap-3">
                                <ShieldAlert className="mt-0.5 size-5 shrink-0 text-amber-600" />
                                <div className="space-y-1">
                                    <h2 className="text-foreground text-lg font-semibold">
                                        Complete without code
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        Use this only after you confirm the
                                        customer yourself. The driver is
                                        recorded as the payment collector and
                                        the customer is notified.
                                    </p>
                                </div>
                            </div>
                            <Select
                                name="method"
                                defaultValue={verificationMethods[0]?.value}
                            >
                                <SelectTrigger
                                    className="w-full"
                                    aria-label="How the customer was verified"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {verificationMethods.map((method) => (
                                        <SelectItem
                                            key={method.value}
                                            value={method.value}
                                        >
                                            {method.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.method} />
                            <Textarea
                                name="note"
                                required
                                rows={3}
                                maxLength={500}
                                placeholder="How did you confirm it was the customer?"
                            />
                            <InputError message={errors.note} />
                            <InputError message={errors.status} />
                            <Button
                                type="submit"
                                variant="outline"
                                className="w-full"
                                disabled={processing}
                            >
                                Complete without code
                            </Button>
                        </>
                    )}
                </Form>
            ) : null}
            {canReportUnavailable ? (
                <Form
                    {...failForm}
                    className="border-border space-y-3 rounded-xl border p-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <h2 className="text-foreground text-lg font-semibold">
                                Can't deliver
                            </h2>
                            <Select
                                name="reason"
                                defaultValue={
                                    failureReasons[0]?.value ??
                                    'customer_unavailable'
                                }
                            >
                                <SelectTrigger
                                    className="w-full"
                                    aria-label="Reason"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {failureReasons.map((reason) => (
                                        <SelectItem
                                            key={reason.value}
                                            value={reason.value}
                                        >
                                            {reason.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.reason} />
                            <Textarea
                                name="note"
                                required
                                rows={3}
                                placeholder="What happened? The branch will read this."
                            />
                            <InputError message={errors.note} />
                            <Button
                                type="submit"
                                variant="outline"
                                className="w-full"
                                disabled={processing}
                            >
                                Mark as not delivered
                            </Button>
                        </>
                    )}
                </Form>
            ) : null}
        </div>
    );
}
