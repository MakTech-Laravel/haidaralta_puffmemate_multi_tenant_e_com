import {
    accountLabelClass,
    accountSelectContentClass,
    accountSelectItemClass,
    accountSelectTriggerClass,
} from '@/components/customer/account-field-styles';
import { ACCOUNT_REGION_OPTIONS } from '@/components/customer/account-region-options';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type AccountRegionSelectProps = {
    id: string;
    value?: string;
    onValueChange?: (value: string) => void;
    label?: string;
};

export function AccountRegionSelect({
    id,
    value,
    onValueChange,
    label = 'Region/State',
}: AccountRegionSelectProps) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className={accountLabelClass}>
                {label}
            </Label>
            <Select value={value || undefined} onValueChange={onValueChange}>
                <SelectTrigger id={id} className={accountSelectTriggerClass}>
                    <SelectValue placeholder="Select" />
                </SelectTrigger>
                <SelectContent className={accountSelectContentClass}>
                    {ACCOUNT_REGION_OPTIONS.map((option) => (
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
        </div>
    );
}
