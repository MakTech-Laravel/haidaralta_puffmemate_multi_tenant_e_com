import { Form } from '@inertiajs/react';
import { UserX } from 'lucide-react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

type DriverOrderUnavailableSheetProps = {
    orderId: string;
    reasons: Array<{ value: string; label: string }>;
};

export function DriverOrderUnavailableSheet({
    orderId,
    reasons,
}: DriverOrderUnavailableSheetProps) {
    const [open, setOpen] = useState(false);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="lg"
                    className="h-12 w-full"
                    data-test="driver-order-unavailable"
                >
                    <UserX data-icon="inline-start" />
                    Unavailable
                </Button>
            </SheetTrigger>
            <SheetContent side="bottom" className="max-h-[85svh]">
                <SheetHeader>
                    <SheetTitle>Customer unavailable</SheetTitle>
                    <SheetDescription>
                        Tell the branch what happened at the door. You can
                        return the goods afterwards.
                    </SheetDescription>
                </SheetHeader>
                <Form
                    {...OrderController.fail.form(orderId)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4 px-4 pb-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`fail-reason-${orderId}`}>
                                    Reason
                                </Label>
                                <select
                                    id={`fail-reason-${orderId}`}
                                    name="reason"
                                    className="border-input bg-background h-11 w-full rounded-md border px-3 text-base"
                                    defaultValue={
                                        reasons[0]?.value ??
                                        'customer_unavailable'
                                    }
                                >
                                    {reasons.map((reason) => (
                                        <option
                                            key={reason.value}
                                            value={reason.value}
                                        >
                                            {reason.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.reason} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`fail-note-${orderId}`}>
                                    Note
                                </Label>
                                <textarea
                                    id={`fail-note-${orderId}`}
                                    name="note"
                                    required
                                    rows={3}
                                    placeholder="What happened at the door"
                                    className="border-input bg-background w-full rounded-md border px-3 py-2 text-base"
                                />
                                <InputError message={errors.note} />
                            </div>
                            <Button
                                type="submit"
                                size="lg"
                                variant="destructive"
                                className="h-12 w-full"
                                disabled={processing}
                            >
                                Mark customer unavailable
                            </Button>
                        </>
                    )}
                </Form>
            </SheetContent>
        </Sheet>
    );
}
