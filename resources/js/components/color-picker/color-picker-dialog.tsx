import type { Ref } from 'react';

import { ColorPickerPanel } from '@/components/color-picker/color-picker-panel';
import { ColorPickerPreviewBar } from '@/components/color-picker/color-picker-preview';
import { ColorPickerTrigger } from '@/components/color-picker/color-picker-trigger';
import type {
    ColorPickerClassNames,
    ColorPickerSize,
    ColorPickerTriggerVariant,
} from '@/components/color-picker/color-picker-types';
import type { ColorPickerState } from '@/components/color-picker/use-color-picker-state';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type ColorPickerDialogProps = {
    id?: string;
    description: string;
    label: string;
    disabled?: boolean;
    error?: string;
    triggerVariant?: ColorPickerTriggerVariant;
    size?: ColorPickerSize;
    classNames?: ColorPickerClassNames;
    dialogTitle: string;
    dialogDescription: string;
    state: ColorPickerState;
    triggerRef: Ref<HTMLButtonElement>;
};

function dialogMaxWidth(size: ColorPickerSize): string {
    if (size === 'sm') {
        return 'sm:max-w-lg';
    }

    if (size === 'lg') {
        return 'sm:max-w-2xl';
    }

    return 'sm:max-w-xl';
}

export function ColorPickerDialog({
    id,
    description,
    label,
    disabled,
    error,
    triggerVariant = 'field',
    size = 'md',
    classNames,
    dialogTitle,
    dialogDescription,
    state,
    triggerRef,
}: ColorPickerDialogProps) {
    const {
        open,
        setOpen,
        panelId,
        workingValue,
        updateWorkingValue,
        setMode,
        confirmPending,
        confirmSelection,
        resetWorkingValue,
        focus,
        labels,
        enabledModes,
        colorPresets,
        solidQuickSelect,
        gradientQuickSelect,
        showQuickSelect,
        showCustomAngle,
    } = state;

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);

                if (!nextOpen) {
                    focus();
                }
            }}
        >
            <div className={cn('h-full', classNames?.shell)}>
                <DialogTrigger asChild>
                    <ColorPickerTrigger
                        ref={triggerRef}
                        id={id}
                        open={open}
                        disabled={disabled}
                        value={state.committedValue}
                        label={label}
                        description={description}
                        error={error}
                        panelId={panelId}
                        classNames={classNames}
                        mode="dialog"
                        triggerVariant={triggerVariant}
                        labels={labels}
                    />
                </DialogTrigger>
            </div>

            <DialogContent
                className={cn(
                    'bg-background text-foreground flex max-h-[min(92vh,760px)] w-full flex-col gap-0 overflow-hidden rounded-2xl p-0',
                    dialogMaxWidth(size),
                    classNames?.dialogContent,
                )}
            >
                <DialogHeader
                    className={cn(
                        'border-border/60 shrink-0 space-y-1 border-b px-5 py-4 text-left',
                        classNames?.dialogHeader,
                    )}
                >
                    <DialogTitle>{dialogTitle}</DialogTitle>
                    <DialogDescription>{dialogDescription}</DialogDescription>
                </DialogHeader>

                <div
                    id={panelId}
                    className={cn(
                        'max-h-[min(70vh,560px)] overflow-y-auto px-5 py-4',
                        classNames?.panel,
                    )}
                >
                    <ColorPickerPanel
                        value={workingValue}
                        onChange={updateWorkingValue}
                        onModeChange={setMode}
                        enabledModes={enabledModes}
                        labels={labels}
                        disabled={disabled}
                        colorPresets={colorPresets}
                        solidQuickSelect={solidQuickSelect}
                        gradientQuickSelect={gradientQuickSelect}
                        showQuickSelect={showQuickSelect}
                        showCustomAngle={showCustomAngle}
                    />
                </div>

                <ColorPickerPreviewBar
                    confirmSelection={confirmSelection}
                    onConfirm={confirmPending}
                    onCancel={() => {
                        setOpen(false);
                        focus();
                    }}
                    onReset={resetWorkingValue}
                    labels={labels}
                    classNames={classNames}
                />
            </DialogContent>
        </Dialog>
    );
}
