import '@ncdai/react-wheel-picker/style.css';

import * as WheelPickerPrimitive from '@ncdai/react-wheel-picker';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

type WheelPickerValue = WheelPickerPrimitive.WheelPickerValue;
type WheelPickerOption<T extends WheelPickerValue = string> =
    WheelPickerPrimitive.WheelPickerOption<T>;
type WheelPickerClassNames = WheelPickerPrimitive.WheelPickerClassNames;

function WheelPickerWrapper({
    className,
    ...props
}: ComponentProps<typeof WheelPickerPrimitive.WheelPickerWrapper>) {
    return (
        <WheelPickerPrimitive.WheelPickerWrapper
            className={cn(
                'w-full px-1',
                '*:data-rwp:first:*:data-rwp-highlight-wrapper:rounded-s-md',
                '*:data-rwp:last:*:data-rwp-highlight-wrapper:rounded-e-md',
                className,
            )}
            {...props}
        />
    );
}

function WheelPicker<T extends WheelPickerValue = string>({
    classNames,
    visibleCount = 20,
    optionItemHeight = 32,
    ...props
}: WheelPickerPrimitive.WheelPickerProps<T>) {
    return (
        <WheelPickerPrimitive.WheelPicker
            visibleCount={visibleCount}
            optionItemHeight={optionItemHeight}
            classNames={{
                optionItem: cn(
                    'text-muted-foreground',
                    classNames?.optionItem,
                ),
                highlightWrapper: cn(
                    'bg-muted text-foreground',
                    classNames?.highlightWrapper,
                ),
                highlightItem: cn(
                    'text-foreground font-medium',
                    classNames?.highlightItem,
                ),
            }}
            {...props}
        />
    );
}

export { WheelPicker, WheelPickerWrapper };
export type { WheelPickerClassNames, WheelPickerOption, WheelPickerValue };
