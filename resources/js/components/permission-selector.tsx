import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import {
    CheckIcon,
    ChevronDown,
    MinusIcon,
    Search,
    ShieldCheck,
} from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type PermissionOption = {
    value: string;
    label: string;
};

export type PermissionGroup = {
    group: string;
    permissions: PermissionOption[];
};

type GroupState = 'none' | 'some' | 'all';

export function PermissionSelector({
    groups,
    selected,
    onChange,
    disabled = false,
}: {
    groups: PermissionGroup[];
    selected: string[];
    onChange: (next: string[]) => void;
    disabled?: boolean;
}) {
    const [query, setQuery] = useState('');
    const [collapsed, setCollapsed] = useState<Record<string, boolean>>({});

    const selectedSet = useMemo(() => new Set(selected), [selected]);

    const allValues = useMemo(
        () => groups.flatMap((group) => group.permissions.map((p) => p.value)),
        [groups],
    );

    const filteredGroups = useMemo(() => {
        const q = query.trim().toLowerCase();

        if (q === '') {
            return groups;
        }

        return groups
            .map((group) => ({
                ...group,
                permissions: group.permissions.filter(
                    (permission) =>
                        permission.value.toLowerCase().includes(q) ||
                        permission.label.toLowerCase().includes(q) ||
                        group.group.toLowerCase().includes(q),
                ),
            }))
            .filter((group) => group.permissions.length > 0);
    }, [groups, query]);

    const groupState = (values: string[]): GroupState => {
        const count = values.filter((value) => selectedSet.has(value)).length;

        if (count === 0) {
            return 'none';
        }

        if (count === values.length) {
            return 'all';
        }

        return 'some';
    };

    const toggleGroup = (values: string[]) => {
        if (disabled) {
            return;
        }

        const next = new Set(selectedSet);
        const state = groupState(values);

        if (state === 'all') {
            values.forEach((value) => next.delete(value));
        } else {
            values.forEach((value) => next.add(value));
        }

        onChange([...next]);
    };

    const toggleItem = (value: string, checked: boolean) => {
        if (disabled) {
            return;
        }

        if (checked) {
            if (selectedSet.has(value)) {
                return;
            }

            onChange([...selected, value]);

            return;
        }

        onChange(selected.filter((item) => item !== value));
    };

    const setAll = (checked: boolean) => {
        if (disabled) {
            return;
        }

        onChange(checked ? [...allValues] : []);
    };

    return (
        <div className="space-y-4">
            {selected.map((permission) => (
                <input
                    key={permission}
                    type="hidden"
                    name="permissions[]"
                    value={permission}
                />
            ))}

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative w-full sm:max-w-xs">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Filter permissions…"
                        className="pl-9"
                        disabled={disabled}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant="secondary" className="gap-1.5">
                        <ShieldCheck className="h-3.5 w-3.5" />
                        {selected.length} / {allValues.length} selected
                    </Badge>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setAll(true)}
                        disabled={
                            disabled || selected.length === allValues.length
                        }
                    >
                        Select all
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() => setAll(false)}
                        disabled={disabled || selected.length === 0}
                    >
                        Clear
                    </Button>
                </div>
            </div>

            <div className="grid gap-3">
                {filteredGroups.map((group) => {
                    const values = group.permissions.map((p) => p.value);
                    const state = groupState(values);
                    const isCollapsed = collapsed[group.group] ?? false;
                    const selectedCount = values.filter((value) =>
                        selectedSet.has(value),
                    ).length;

                    return (
                        <div
                            key={group.group}
                            className={cn(
                                'bg-card overflow-hidden rounded-xl border transition-colors',
                                state !== 'none' && 'border-primary/40',
                            )}
                        >
                            <div className="bg-muted/40 flex items-center justify-between gap-3 px-4 py-3">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setCollapsed((current) => ({
                                            ...current,
                                            [group.group]: !isCollapsed,
                                        }))
                                    }
                                    className="flex flex-1 items-center gap-2 text-left"
                                >
                                    <ChevronDown
                                        className={cn(
                                            'text-muted-foreground h-4 w-4 transition-transform',
                                            isCollapsed && '-rotate-90',
                                        )}
                                    />
                                    <span className="text-sm font-semibold">
                                        {group.group}
                                    </span>
                                    <Badge
                                        variant={
                                            state === 'all'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                        className="ml-1 tabular-nums"
                                    >
                                        {selectedCount}/{values.length}
                                    </Badge>
                                </button>

                                <label className="text-muted-foreground flex cursor-pointer items-center gap-2 text-xs font-medium select-none">
                                    Select group
                                    <TriStateCheckbox
                                        state={state}
                                        disabled={disabled}
                                        onToggle={() => toggleGroup(values)}
                                    />
                                </label>
                            </div>

                            <AnimatePresence initial={false}>
                                {!isCollapsed && (
                                    <motion.div
                                        initial={{ height: 0, opacity: 0 }}
                                        animate={{ height: 'auto', opacity: 1 }}
                                        exit={{ height: 0, opacity: 0 }}
                                        transition={{ duration: 0.2 }}
                                    >
                                        <div className="grid grid-cols-1 gap-x-4 gap-y-1 p-3 sm:grid-cols-2">
                                            {group.permissions.map(
                                                (permission) => {
                                                    const checked =
                                                        selectedSet.has(
                                                            permission.value,
                                                        );

                                                    return (
                                                        <Label
                                                            key={
                                                                permission.value
                                                            }
                                                            htmlFor={`permission-${permission.value}`}
                                                            className={cn(
                                                                'hover:bg-muted/60 flex cursor-pointer items-start gap-2.5 rounded-md px-2.5 py-2 text-sm font-normal transition-colors',
                                                                checked &&
                                                                    'bg-muted',
                                                                disabled &&
                                                                    'cursor-not-allowed opacity-60',
                                                            )}
                                                        >
                                                            <Checkbox
                                                                id={`permission-${permission.value}`}
                                                                checked={
                                                                    checked
                                                                }
                                                                disabled={
                                                                    disabled
                                                                }
                                                                className="mt-0.5"
                                                                onCheckedChange={(
                                                                    value,
                                                                ) =>
                                                                    toggleItem(
                                                                        permission.value,
                                                                        value ===
                                                                            true,
                                                                    )
                                                                }
                                                            />
                                                            <span className="min-w-0">
                                                                <span className="block text-sm">
                                                                    {
                                                                        permission.label
                                                                    }
                                                                </span>
                                                                <span className="text-muted-foreground block truncate font-mono text-[11px]">
                                                                    {
                                                                        permission.value
                                                                    }
                                                                </span>
                                                            </span>
                                                        </Label>
                                                    );
                                                },
                                            )}
                                        </div>
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>
                    );
                })}

                {filteredGroups.length === 0 && (
                    <div className="text-muted-foreground rounded-xl border border-dashed py-10 text-center text-sm">
                        No permissions match “{query}”.
                    </div>
                )}
            </div>
        </div>
    );
}

function TriStateCheckbox({
    state,
    onToggle,
    disabled,
}: {
    state: GroupState;
    onToggle: () => void;
    disabled?: boolean;
}) {
    return (
        <CheckboxPrimitive.Root
            checked={
                state === 'all'
                    ? true
                    : state === 'some'
                      ? 'indeterminate'
                      : false
            }
            onCheckedChange={onToggle}
            disabled={disabled}
            className="border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary data-[state=indeterminate]:bg-primary data-[state=indeterminate]:text-primary-foreground data-[state=indeterminate]:border-primary peer size-4 shrink-0 rounded-[4px] border shadow-xs outline-none disabled:cursor-not-allowed disabled:opacity-50"
        >
            <CheckboxPrimitive.Indicator className="flex items-center justify-center text-current">
                {state === 'some' ? (
                    <MinusIcon className="size-3.5" />
                ) : (
                    <CheckIcon className="size-3.5" />
                )}
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}
