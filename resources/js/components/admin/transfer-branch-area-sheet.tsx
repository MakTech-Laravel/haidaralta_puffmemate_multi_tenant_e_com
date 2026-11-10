import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { TRANSFER_REASON_MAX } from '@/components/admin/transfer-employee-sheet';
import InputError from '@/components/input-error';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

type TransferBranchAreaSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    action: FormAction;
    areaName: string;
    currentBranchId?: number | null;
    currentBranchName?: string | null;
    branchOptionsUrl: string;
};

export function TransferBranchAreaSheet({
    open,
    onOpenChange,
    action,
    areaName,
    currentBranchId = null,
    currentBranchName = null,
    branchOptionsUrl,
}: TransferBranchAreaSheetProps) {
    const form = useForm({
        to_branch_id: '',
        effective_date: '',
        reason: '',
    });

    const [destinationLabel, setDestinationLabel] = useState<string | null>(
        null,
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        form.setData({
            to_branch_id: '',
            effective_date: '',
            reason: '',
        });
        form.clearErrors();
        setDestinationLabel(null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, currentBranchId]);

    const submit = (): void => {
        form.post(action.action, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    const reasonLength = form.data.reason.length;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-lg"
            >
                <SheetHeader className="space-y-1.5 border-b p-6 text-left">
                    <SheetTitle>Add to branch</SheetTitle>
                    <SheetDescription>
                        Link “{areaName}” to another branch. Existing branch
                        links stay; history is kept for audit.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <div className="bg-muted/50 flex flex-col gap-1.5 rounded-lg border px-4 py-3">
                        <Label className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            Already on
                        </Label>
                        <p className="text-sm font-medium">
                            {currentBranchName ?? '—'}
                        </p>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label>
                            Add to branch{' '}
                            <span className="text-primary">*</span>
                        </Label>
                        <InfiniteScrollCombobox
                            optionsUrl={branchOptionsUrl}
                            value={form.data.to_branch_id || null}
                            onValueChange={(value, option) => {
                                form.setData('to_branch_id', value ?? '');
                                setDestinationLabel(option?.label ?? null);
                            }}
                            selectedLabel={destinationLabel}
                            placeholder="Select branch"
                            searchPlaceholder="Search branches…"
                            emptyText="No branch found."
                            descriptionKey={null}
                            className="w-full"
                        />
                        <InputError message={form.errors.to_branch_id} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label>
                            Effective date{' '}
                            <span className="text-primary">*</span>
                        </Label>
                        <DatePicker
                            value={form.data.effective_date || undefined}
                            onChange={(value) =>
                                form.setData('effective_date', value)
                            }
                            placeholder="Select date"
                        />
                        <InputError message={form.errors.effective_date} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <div className="flex items-center justify-between gap-2">
                            <Label htmlFor="area-transfer-reason">
                                Reason <span className="text-primary">*</span>
                            </Label>
                            <span
                                className={cn(
                                    'text-muted-foreground text-xs tabular-nums',
                                    reasonLength > TRANSFER_REASON_MAX &&
                                        'text-destructive',
                                )}
                            >
                                {reasonLength}/{TRANSFER_REASON_MAX}
                            </span>
                        </div>
                        <Textarea
                            id="area-transfer-reason"
                            rows={3}
                            value={form.data.reason}
                            maxLength={TRANSFER_REASON_MAX}
                            placeholder="Why add this area to another branch?"
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                        />
                        <InputError message={form.errors.reason} />
                    </div>
                </div>

                <SheetFooter className="border-t p-6 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        disabled={form.processing}
                        onClick={submit}
                    >
                        {form.processing ? <Spinner /> : null}
                        Add to branch
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
