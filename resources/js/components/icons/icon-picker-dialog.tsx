import type { Ref } from 'react';

import { IconPickerPanel } from '@/components/icons/icon-picker-panel';
import { IconPickerPreview } from '@/components/icons/icon-picker-preview';
import { IconPickerTrigger } from '@/components/icons/icon-picker-trigger';
import type {
    LucideIconPickerClassNames,
    LucideIconPickerDensity,
    LucideIconPickerSize,
    LucideIconPickerTriggerVariant,
} from '@/components/icons/lucide-icon-picker-types';
import type { IconPickerState } from '@/components/icons/use-icon-picker-state';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type IconPickerDialogProps = {
    id?: string;
    description: string;
    label: string;
    disabled?: boolean;
    error?: string;
    showSparkles?: boolean;
    showRecents?: boolean;
    showCategories?: boolean;
    triggerVariant?: LucideIconPickerTriggerVariant;
    density?: LucideIconPickerDensity;
    size?: LucideIconPickerSize;
    classNames?: LucideIconPickerClassNames;
    dialogTitle: string;
    dialogDescription: string;
    state: IconPickerState;
    triggerRef: Ref<HTMLButtonElement>;
};

function dialogMaxWidth(size: LucideIconPickerSize): string {
    if (size === 'sm') {
        return 'sm:max-w-lg';
    }

    if (size === 'lg') {
        return 'sm:max-w-3xl lg:max-w-4xl';
    }

    return 'sm:max-w-2xl lg:max-w-3xl';
}

export function IconPickerDialog({
    id,
    description,
    label,
    disabled,
    error,
    showSparkles,
    showRecents = true,
    showCategories = true,
    triggerVariant = 'field',
    density = 'comfortable',
    size = 'md',
    classNames,
    dialogTitle,
    dialogDescription,
    state,
    triggerRef,
}: IconPickerDialogProps) {
    const {
        open,
        setOpen,
        displayIcon,
        displayLabel,
        activeIcon,
        activeLabel,
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
        confirmPending,
        confirmSelection,
        focus,
        category,
        setCategory,
        availableCategories,
        recentOptions,
        clearRecents,
        pendingIcon,
        labels,
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
            <div className={cn(classNames?.shell)}>
                <DialogTrigger asChild>
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
                        mode="dialog"
                        triggerVariant={triggerVariant}
                    />
                </DialogTrigger>
            </div>

            <DialogContent
                className={cn(
                    'flex max-h-[min(90vh,720px)] w-full flex-col gap-0 overflow-hidden bg-background p-0 text-foreground',
                    dialogMaxWidth(size),
                    classNames?.dialogContent,
                )}
                onKeyDown={(event) => {
                    if (
                        confirmSelection &&
                        event.key === 'Enter' &&
                        !(event.target instanceof HTMLInputElement)
                    ) {
                        event.preventDefault();
                        confirmPending();
                    }
                }}
            >
                <DialogHeader
                    className={cn(
                        'shrink-0 space-y-1.5 border-b border-border px-5 py-5 text-left',
                        classNames?.dialogHeader,
                    )}
                >
                    <DialogTitle>{dialogTitle}</DialogTitle>
                    <DialogDescription>{dialogDescription}</DialogDescription>
                </DialogHeader>

                <div className="grid min-h-0 flex-1 overflow-hidden lg:grid-cols-[minmax(0,1fr)_240px]">
                    <div className="min-h-0 overflow-hidden">
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
                            pendingIcon={pendingIcon}
                            onSelect={selectIcon}
                            categories={availableCategories}
                            activeCategory={category}
                            onCategoryChange={setCategory}
                            recentOptions={recentOptions}
                            onClearRecents={clearRecents}
                            showCategories={showCategories}
                            showRecents={showRecents}
                            density={density}
                            fillHeight
                            labels={labels}
                            classNames={classNames}
                        />
                    </div>

                    <div className="hidden min-h-0 border-l border-border lg:block">
                        <IconPickerPreview
                            icon={activeIcon}
                            label={activeLabel}
                            committedIcon={displayIcon}
                            layout="rail"
                            confirmSelection={confirmSelection}
                            onConfirm={confirmPending}
                            onCancel={() => {
                                setOpen(false);
                                focus();
                            }}
                            labels={labels}
                            classNames={classNames}
                        />
                    </div>
                </div>

                <div className="lg:hidden">
                    <IconPickerPreview
                        icon={activeIcon}
                        label={activeLabel}
                        committedIcon={displayIcon}
                        layout="bar"
                        confirmSelection={confirmSelection}
                        onConfirm={confirmPending}
                        onCancel={() => {
                            setOpen(false);
                            focus();
                        }}
                        labels={labels}
                        classNames={classNames}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
