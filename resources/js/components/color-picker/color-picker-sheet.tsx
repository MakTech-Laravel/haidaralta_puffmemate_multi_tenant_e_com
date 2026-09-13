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
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';

type ColorPickerSheetProps = {
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

function sheetMaxWidth(size: ColorPickerSize): string {
    if (size === 'sm') {
        return 'sm:max-w-md';
    }

    if (size === 'lg') {
        return 'sm:max-w-xl';
    }

    return 'sm:max-w-lg';
}

export function ColorPickerSheet({
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
}: ColorPickerSheetProps) {
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
        <Sheet
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);

                if (!nextOpen) {
                    focus();
                }
            }}
        >
            <div className={cn(classNames?.shell)}>
                <SheetTrigger asChild>
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
                        mode="sheet"
                        triggerVariant={triggerVariant}
                        labels={labels}
                    />
                </SheetTrigger>
            </div>

            <SheetContent
                side="right"
                className={cn(
                    'bg-background text-foreground flex h-full w-full flex-col gap-0 overflow-hidden p-0',
                    sheetMaxWidth(size),
                    classNames?.sheetContent,
                )}
            >
                <SheetHeader className="border-border shrink-0 space-y-1.5 border-b px-5 py-5 pr-12 text-left">
                    <SheetTitle>{dialogTitle}</SheetTitle>
                    <SheetDescription>{dialogDescription}</SheetDescription>
                </SheetHeader>

                <div
                    id={panelId}
                    className={cn(
                        'min-h-0 flex-1 overflow-y-auto px-5 py-4',
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
            </SheetContent>
        </Sheet>
    );
}
