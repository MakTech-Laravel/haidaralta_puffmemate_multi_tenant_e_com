import { Form, router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

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
                            <div className="grid gap-2">
                                <Label htmlFor="return-request-type">
                                    Request
                                </Label>
                                <Select name="type" defaultValue="refund">
                                    <SelectTrigger
                                        id="return-request-type"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="refund">
                                            Refund
                                        </SelectItem>
                                        <SelectItem value="return">
                                            Return
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="return-request-reason">
                                    Reason
                                </Label>
                                <Input
                                    id="return-request-reason"
                                    name="reason"
                                    required
                                    placeholder="Damaged, wrong item, missing item…"
                                />
                                <InputError message={errors.reason} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="return-request-note">
                                    Details
                                </Label>
                                <Textarea
                                    id="return-request-note"
                                    name="note"
                                    rows={3}
                                    placeholder="What is wrong with the order"
                                />
                                <InputError message={errors.note} />
                            </div>
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
