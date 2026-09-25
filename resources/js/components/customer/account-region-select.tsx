import {
    accountLabelClass,
    accountSelectContentClass,
    accountSelectItemClass,
    accountSelectTriggerClass,
} from '@/components/customer/account-field-styles';
import { ACCOUNT_REGION_OPTIONS } from '@/components/customer/account-region-options';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type AccountRegionOption = {
    value: string;
    label: string;
};

type AccountRegionSelectProps = {
    id: string;
    value?: string;
    onValueChange?: (value: string) => void;
    label?: string;
    name?: string;
    error?: string;
    regions?: readonly AccountRegionOption[];
};

export function AccountRegionSelect({
    id,
    value,
    onValueChange,
    label = 'Region/State',
    name,
    error,
    regions = ACCOUNT_REGION_OPTIONS,
}: AccountRegionSelectProps) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className={accountLabelClass}>
                {label}
            </Label>
            {name ? <input type="hidden" name={name} value={value ?? ''} /> : null}
            <Select value={value || undefined} onValueChange={onValueChange}>
                <SelectTrigger id={id} className={accountSelectTriggerClass}>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent className={accountSelectContentClass}>
                    {regions.map((option) => (
                        <SelectItem
                            key={option.value}
                            value={option.value}
                            className={accountSelectItemClass}
                        >
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}
