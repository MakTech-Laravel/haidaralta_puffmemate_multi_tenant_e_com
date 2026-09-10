import { Clock } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const EMPTY_VALUE = '__none__';

const TIME_OPTIONS = Array.from({ length: 24 * 4 }, (_, index) => {
    const hours = Math.floor(index / 4);
    const minutes = (index % 4) * 15;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
});

function normalizeTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    // Accept "HH:mm" or "HH:mm:ss" from the API.
    const match = /^(\d{2}):(\d{2})/.exec(value);

    if (!match) {
        return value;
    }

    return `${match[1]}:${match[2]}`;
}

type TimeSelectProps = {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    'aria-invalid'?: boolean;
};

export function TimeSelect({
    id,
    value,
    onChange,
    placeholder = 'Select time',
    disabled = false,
    className,
    'aria-invalid': ariaInvalid,
}: TimeSelectProps) {
    const normalized = normalizeTime(value);
    const options =
        normalized && !TIME_OPTIONS.includes(normalized)
            ? [normalized, ...TIME_OPTIONS]
            : TIME_OPTIONS;

    return (
        <Select
            value={normalized || EMPTY_VALUE}
            onValueChange={(next) =>
                onChange(next === EMPTY_VALUE ? '' : next)
            }
            disabled={disabled}
        >
            <SelectTrigger
                id={id}
                aria-invalid={ariaInvalid}
                className={cn('w-full', className)}
            >
                <Clock className="text-muted-foreground" />
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectItem value={EMPTY_VALUE}>
                        {placeholder}
                    </SelectItem>
                    {options.map((time) => (
                        <SelectItem key={time} value={time}>
                            {time}
                        </SelectItem>
                    ))}
                </SelectGroup>
            </SelectContent>
        </Select>
    );
}
