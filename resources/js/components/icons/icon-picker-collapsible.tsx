import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import type { Ref } from 'react';

import { IconPickerPanel } from '@/components/icons/icon-picker-panel';
import { IconPickerTrigger } from '@/components/icons/icon-picker-trigger';
import type {
    LucideIconPickerClassNames,
    LucideIconPickerDensity,
    LucideIconPickerPanelAlign,
    LucideIconPickerPanelBehavior,
    LucideIconPickerTriggerVariant,
} from '@/components/icons/lucide-icon-picker-types';
import type { IconPickerState } from '@/components/icons/use-icon-picker-state';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';

type IconPickerCollapsibleProps = {
    id?: string;
    description: string;
    label: string;
    disabled?: boolean;
    error?: string;
    showSparkles?: boolean;
    showRecents?: boolean;
    showCategories?: boolean;
    triggerVariant?: LucideIconPickerTriggerVariant;
    panelBehavior?: LucideIconPickerPanelBehavior;
    panelAlign?: LucideIconPickerPanelAlign;
    density?: LucideIconPickerDensity;
    classNames?: LucideIconPickerClassNames;
    state: IconPickerState;
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

export function IconPickerCollapsible({
    id,
    description,
    label,
    disabled,
    error,
    showSparkles,
    showRecents = true,
    showCategories = true,
    triggerVariant = 'field',
    panelBehavior: panelBehaviorProp,
    panelAlign = 'auto',
    density = 'comfortable',
    classNames,
    state,
    triggerRef,
}: IconPickerCollapsibleProps) {
    const panelBehavior =
        panelBehaviorProp ??
        (triggerVariant === 'compact' || triggerVariant === 'ghost'
            ? 'overlay'
            : 'inline');

    const shellRef = useRef<HTMLDivElement>(null);

    // Only `panelAlign: 'auto'` needs a measurement; an explicit alignment is
    // derived from the prop rather than mirrored into state.
    const [measuredAlign, setMeasuredAlign] = useState<AlignSide | null>(null);
    const alignSide: AlignSide =
        panelAlign === 'start' || panelAlign === 'end'
            ? panelAlign
            : (measuredAlign ??
              (triggerVariant === 'compact' ? 'end' : 'start'));

    const {
        open,
        setOpen,
        displayIcon,
        displayLabel,
        searchId,
        gridId,
        statusId,
        query,
        setQuery,
        searchPlaceholder,
        catalogLoading,
        statusMessage,
        isSearchPending,
        hadInvalidDefault,
        filteredOptions,
        deferredQuery,
        selectIcon,
        focus,
        focusSearch,
        category,
        setCategory,
        availableCategories,
        recentOptions,
        clearRecents,
        labels,
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
        const panelWidth = Math.min(window.innerWidth - 32, 352);
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

        const frame = window.requestAnimationFrame(() => {
            focusSearch();
        });

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
            window.cancelAnimationFrame(frame);
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [focus, focusSearch, open, panelBehavior, setOpen]);

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
                    <IconPickerTrigger
                        ref={triggerRef}
                        id={id}
                        open={open}
                        disabled={disabled}
                        displayIcon={displayIcon}
                        displayLabel={displayLabel}
                        description={description}
                        showSparkles={showSparkles}
                        error={error}
                        gridId={gridId}
                        statusId={statusId}
                        classNames={classNames}
                        mode="collapsible"
                        triggerVariant={triggerVariant}
                    />
                </CollapsibleTrigger>

                <CollapsibleContent
                    className={cn(
                        'data-[state=closed]:animate-out data-[state=open]:animate-in',
                        panelBehavior === 'overlay' &&
                            cn(
                                'absolute top-full z-50 mt-2 w-[min(calc(100vw-2rem),22rem)]',
                                alignSide === 'end' ? 'right-0' : 'left-0',
                            ),
                        panelBehavior === 'inline' && 'mt-2',
                    )}
                >
                    <div
                        className={cn(
                            'overflow-hidden rounded-xl border border-border bg-popover text-popover-foreground',
                            panelBehavior === 'overlay'
                                ? 'shadow-lg'
                                : 'shadow-xs',
                            disabled && 'pointer-events-none opacity-60',
                        )}
                    >
                        <IconPickerPanel
                            searchId={searchId}
                            gridId={gridId}
                            statusId={statusId}
                            label={label}
                            query={query}
                            onQueryChange={setQuery}
                            onEscape={() => {
                                setOpen(false);
                                focus();
                            }}
                            searchPlaceholder={searchPlaceholder}
                            disabled={disabled}
                            catalogLoading={catalogLoading}
                            statusMessage={statusMessage}
                            error={error}
                            isSearchPending={isSearchPending}
                            hadInvalidDefault={hadInvalidDefault}
                            filteredOptions={filteredOptions}
                            deferredQuery={deferredQuery}
                            selectedIcon={displayIcon}
                            onSelect={selectIcon}
                            categories={availableCategories}
                            activeCategory={category}
                            onCategoryChange={setCategory}
                            recentOptions={recentOptions}
                            onClearRecents={clearRecents}
                            showCategories={showCategories}
                            showRecents={showRecents}
                            density={density}
                            labels={labels}
                            classNames={classNames}
                        />
                    </div>
                </CollapsibleContent>
            </div>
        </Collapsible>
    );
}
