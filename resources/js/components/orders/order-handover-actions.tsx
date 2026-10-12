import { Form } from '@inertiajs/react';
import { DriverDeliverOtpForm } from '@/components/driver/driver-deliver-otp-form';
import { Button } from '@/components/ui/button';

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
                            <select
                                name="reason"
                                className="border-input bg-background h-10 w-full rounded-md border px-3 text-sm"
                                defaultValue={
                                    failureReasons[0]?.value ??
                                    'customer_unavailable'
                                }
                            >
                                {failureReasons.map((reason) => (
                                    <option
                                        key={reason.value}
                                        value={reason.value}
                                    >
                                        {reason.label}
                                    </option>
                                ))}
                            </select>
                            {errors.reason ? (
                                <p className="text-destructive text-sm">
                                    {errors.reason}
                                </p>
                            ) : null}
                            <textarea
                                name="note"
                                required
                                rows={3}
                                placeholder="What happened at the door"
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            />
                            {errors.note ? (
                                <p className="text-destructive text-sm">
                                    {errors.note}
                                </p>
                            ) : null}
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
