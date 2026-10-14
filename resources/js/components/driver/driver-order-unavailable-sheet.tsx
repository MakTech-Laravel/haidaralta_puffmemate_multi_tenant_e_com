import { Form } from '@inertiajs/react';
import { PackageX } from 'lucide-react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';

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
                    <PackageX data-icon="inline-start" />
                    Can't deliver
                </Button>
            </SheetTrigger>
            <SheetContent
                side="bottom"
                className="max-h-[85svh] gap-0 overflow-y-auto rounded-t-3xl sm:mx-auto sm:max-w-lg"
            >
                <div className="bg-muted mx-auto mt-2.5 h-1.5 w-10 shrink-0 rounded-full" />
                <SheetHeader>
                    <SheetTitle>Can't deliver</SheetTitle>
                    <SheetDescription>
                        Tell the branch why this order could not be delivered.
                        You can return the goods afterwards.
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
                                <Select
                                    name="reason"
                                    defaultValue={
                                        reasons[0]?.value ??
                                        'customer_unavailable'
                                    }
                                >
                                    <SelectTrigger
                                        id={`fail-reason-${orderId}`}
                                        className="h-11 w-full rounded-xl"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent className="rounded-xl">
                                        {reasons.map((reason) => (
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
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`fail-note-${orderId}`}>
                                    Note
                                </Label>
                                <Textarea
                                    id={`fail-note-${orderId}`}
                                    name="note"
                                    required
                                    rows={3}
                                    placeholder="What happened? The branch will read this."
                                    className="rounded-xl"
                                />
                                <InputError message={errors.note} />
                            </div>
                            <Button
                                type="submit"
                                size="lg"
                                variant="destructive"
                                className="h-12 w-full rounded-full"
                                disabled={processing}
                            >
                                Mark as not delivered
                            </Button>
                        </>
                    )}
                </Form>
            </SheetContent>
        </Sheet>
    );
}
