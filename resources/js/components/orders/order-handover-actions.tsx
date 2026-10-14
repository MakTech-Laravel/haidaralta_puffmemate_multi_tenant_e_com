import { Form } from '@inertiajs/react';
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

type FailureReason = {
    value: string;
    label: string;
};

type OrderHandoverActionsProps = {
    orderId: string | number;
    deliverForm: {
        action: string;
        method: 'post';
    };
    failForm: {
        action: string;
        method: 'post';
    };
    canComplete: boolean;
    canReportUnavailable: boolean;
    failureReasons: FailureReason[];
};

export function OrderHandoverActions({
    orderId,
    deliverForm,
    failForm,
    canComplete,
    canReportUnavailable,
    failureReasons,
}: OrderHandoverActionsProps) {
    if (!canComplete && !canReportUnavailable) {
        return null;
    }

    return (
        <div className="space-y-3">
            {canComplete ? (
                <DriverDeliverOtpForm
                    orderId={String(orderId)}
                    form={deliverForm}
                />
            ) : null}
            {canReportUnavailable ? (
                <Form
                    {...failForm}
                    className="border-border space-y-3 rounded-xl border p-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <h2 className="text-foreground text-lg font-semibold">
                                Customer unavailable
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
                                placeholder="What happened at the door"
                            />
                            <InputError message={errors.note} />
                            <Button
                                type="submit"
                                variant="outline"
                                className="w-full"
                                disabled={processing}
                            >
                                Customer unavailable
                            </Button>
                        </>
                    )}
                </Form>
            ) : null}
        </div>
    );
}
