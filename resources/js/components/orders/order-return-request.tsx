import { Form, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

export type OrderReturnRequestPayload = {
    id: number;
    type: string;
    typeLabel: string;
    reason: string;
    note: string | null;
    status: string;
    statusLabel: string;
};

type OrderReturnRequestProps = {
    canRequest: boolean;
    request: OrderReturnRequestPayload | null;
    form?: {
        action: string;
        method: 'post';
    };
    approveUrl?: string;
    rejectUrl?: string;
};

export function OrderReturnRequest({
    canRequest,
    request,
    form,
    approveUrl,
    rejectUrl,
}: OrderReturnRequestProps) {
    const [processing, setProcessing] = useState(false);

    if (request === null && !canRequest) {
        return null;
    }

    return (
        <section className="border-border space-y-3 rounded-xl border p-4">
            <h2 className="text-foreground text-lg font-semibold">
                Return or refund
            </h2>
            {request ? (
                <div className="space-y-1 text-sm">
                    <p>
                        {request.typeLabel}: {request.reason}
                    </p>
                    {request.note ? (
                        <p className="text-muted-foreground">{request.note}</p>
                    ) : null}
                    <p className="font-medium">{request.statusLabel}</p>
                </div>
            ) : null}
            {canRequest && form ? (
                <Form {...form} className="space-y-3">
                    {({ processing: submitting, errors }) => (
                        <>
                            <select
                                name="type"
                                defaultValue="refund"
                                className="border-input bg-background h-10 w-full rounded-md border px-3 text-sm"
                            >
                                <option value="refund">Refund</option>
                                <option value="return">Return</option>
                            </select>
                            <input
                                name="reason"
                                required
                                placeholder="Reason"
                                className="border-input bg-background h-10 w-full rounded-md border px-3 text-sm"
                            />
                            {errors.reason ? (
                                <p className="text-destructive text-sm">
                                    {errors.reason}
                                </p>
                            ) : null}
                            <textarea
                                name="note"
                                rows={3}
                                placeholder="What is wrong with the order"
                                className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            />
                            <Button type="submit" disabled={submitting}>
                                Request
                            </Button>
                        </>
                    )}
                </Form>
            ) : null}
            {request?.status === 'requested' && approveUrl && rejectUrl ? (
                <div className="flex gap-2">
                    <Button
                        type="button"
                        disabled={processing}
                        onClick={() => {
                            setProcessing(true);
                            router.post(
                                approveUrl,
                                {},
                                { onFinish: () => setProcessing(false) },
                            );
                        }}
                    >
                        Approve
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={processing}
                        onClick={() => {
                            setProcessing(true);
                            router.post(
                                rejectUrl,
                                {},
                                { onFinish: () => setProcessing(false) },
                            );
                        }}
                    >
                        Reject
                    </Button>
                </div>
            ) : null}
        </section>
    );
}
