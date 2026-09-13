import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import type { Ref } from 'react';

import { ColorPickerPanel } from '@/components/color-picker/color-picker-panel';
import { ColorPickerPreviewBar } from '@/components/color-picker/color-picker-preview';
import { ColorPickerTrigger } from '@/components/color-picker/color-picker-trigger';
import type {
    ColorPickerClassNames,
    ColorPickerPanelAlign,
    ColorPickerPanelBehavior,
    ColorPickerTriggerVariant,
} from '@/components/color-picker/color-picker-types';
import type { ColorPickerState } from '@/components/color-picker/use-color-picker-state';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';

type ColorPickerCollapsibleProps = {
    id?: string;
    description: string;
    label: string;
    disabled?: boolean;
    error?: string;
    triggerVariant?: ColorPickerTriggerVariant;
    panelBehavior?: ColorPickerPanelBehavior;
    panelAlign?: ColorPickerPanelAlign;
    classNames?: ColorPickerClassNames;
    state: ColorPickerState;
    triggerRef: Ref<HTMLButtonElement>;
};

type AlignSide = 'start' | 'end';

function focusableWithin(root: HTMLElement): HTMLElement[] {
    return Array.from(
        root.querySelectorAll<HTMLElement>(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    ).filter((el) => !el.hasAttribute('disabled') && el.tabIndex !== -1);
}

export function ColorPickerCollapsible({
    id,
    description,
    label,
    disabled,
    error,
    triggerVariant = 'field',
    panelBehavior: panelBehaviorProp,
    panelAlign = 'auto',
    classNames,
    state,
    triggerRef,
}: ColorPickerCollapsibleProps) {
    const panelBehavior =
        panelBehaviorProp ??
        (triggerVariant === 'compact' || triggerVariant === 'ghost'
            ? 'overlay'
            : 'inline');

    const shellRef = useRef<HTMLDivElement>(null);
    const [measuredAlign, setMeasuredAlign] = useState<AlignSide | null>(null);
    const alignSide: AlignSide =
        panelAlign === 'start' || panelAlign === 'end'
            ? panelAlign
            : (measuredAlign ??
              (triggerVariant === 'compact' ? 'end' : 'start'));

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
        committedValue,
    } = state;

    useLayoutEffect(() => {
        if (!open || panelBehavior !== 'overlay' || panelAlign !== 'auto') {
            return;
        }

        const shell = shellRef.current;

        if (!shell) {
            return;
        }

        const rect = shell.getBoundingClientRect();
        const panelWidth = Math.min(window.innerWidth - 32, 400);
        const spaceRight = window.innerWidth - rect.left;
        const spaceLeft = rect.right;

        setMeasuredAlign(
            spaceRight >= panelWidth || spaceRight >= spaceLeft
                ? 'start'
                : 'end',
        );
    }, [open, panelAlign, panelBehavior]);

    useEffect(() => {
        if (!open || panelBehavior !== 'overlay') {
            return;
        }

        const onPointerDown = (event: MouseEvent) => {
            const target = event.target as Node | null;

            if (!target || !shellRef.current?.contains(target)) {
                setOpen(false);
                focus();
            }
        };

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Tab' || !shellRef.current) {
                return;
            }

            const focusables = focusableWithin(shellRef.current);

            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement as HTMLElement | null;

            if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [focus, open, panelBehavior, setOpen]);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <div
                ref={shellRef}
                className={cn(
                    'relative flex min-w-0 flex-col items-stretch',
                    classNames?.shell,
                )}
            >
                <CollapsibleTrigger asChild>
                    <ColorPickerTrigger
                        ref={triggerRef}
                        id={id}
                        open={open}
                        disabled={disabled}
                        value={committedValue}
                        label={label}
                        description={description}
                        error={error}
                        panelId={panelId}
                        classNames={classNames}
                        mode="collapsible"
                        triggerVariant={triggerVariant}
                        labels={labels}
                    />
                </CollapsibleTrigger>

                <CollapsibleContent
                    className={cn(
                        'data-[state=closed]:animate-out data-[state=open]:animate-in',
                        panelBehavior === 'overlay' &&
                            cn(
                                'absolute top-full z-50 mt-2 w-[min(calc(100vw-2rem),24rem)]',
                                alignSide === 'end' ? 'right-0' : 'left-0',
                            ),
                        panelBehavior === 'inline' && 'mt-2',
                    )}
                >
                    <div
                        className={cn(
                            'border-border bg-popover text-popover-foreground overflow-hidden rounded-xl border',
                            panelBehavior === 'overlay'
                                ? 'shadow-lg'
                                : 'shadow-xs',
                            disabled && 'pointer-events-none opacity-60',
                        )}
                    >
                        <div
                            id={panelId}
                            className={cn('px-4 py-4', classNames?.panel)}
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
                        {confirmSelection ? (
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
                        ) : null}
                    </div>
                </CollapsibleContent>
            </div>
        </Collapsible>
    );
}
