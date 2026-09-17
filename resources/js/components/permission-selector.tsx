import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { CheckIcon, MinusIcon, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
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
    const [activeGroup, setActiveGroup] = useState<string | null>(null);

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

    const resolvedGroup =
        activeGroup !== null &&
        filteredGroups.some((group) => group.group === activeGroup)
            ? activeGroup
            : null;

    const visibleGroups =
        resolvedGroup === null
            ? filteredGroups
            : filteredGroups.filter((group) => group.group === resolvedGroup);

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

        if (groupState(values) === 'all') {
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
        <div className="overflow-hidden rounded-xl border">
            {selected.map((permission) => (
                <input
                    key={permission}
                    type="hidden"
                    name="permissions[]"
                    value={permission}
                />
            ))}

            <div className="flex flex-col gap-2 border-b px-3 py-2 sm:flex-row sm:items-center">
                <div className="relative min-w-0 flex-1">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2" />
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search permissions…"
                        className="h-8 pl-8"
                        disabled={disabled}
                        autoComplete="off"
                    />
                </div>
                <div className="flex items-center gap-1">
                    <span className="text-muted-foreground px-1 text-xs tabular-nums">
                        {selected.length}/{allValues.length}
                    </span>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="h-8 px-2"
                        onClick={() => setAll(true)}
                        disabled={
                            disabled || selected.length === allValues.length
                        }
                    >
                        All
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="h-8 px-2"
                        onClick={() => setAll(false)}
                        disabled={disabled || selected.length === 0}
                    >
                        None
                    </Button>
                </div>
            </div>

            <div className="grid md:grid-cols-[11rem_minmax(0,1fr)]">
                <nav
                    aria-label="Permission groups"
                    className="flex gap-1 overflow-x-auto border-b p-1.5 md:h-72 md:flex-col md:overflow-y-auto md:border-r md:border-b-0 md:p-0"
                >
                    <GroupTab
                        label="All"
                        count={`${selected.length}/${allValues.length}`}
                        active={resolvedGroup === null}
                        disabled={disabled}
                        onClick={() => setActiveGroup(null)}
                    />
                    {filteredGroups.map((group) => {
                        const values = group.permissions.map((p) => p.value);
                        const granted = values.filter((value) =>
                            selectedSet.has(value),
                        ).length;

                        return (
                            <GroupTab
                                key={group.group}
                                label={group.group}
                                count={`${granted}/${values.length}`}
                                active={resolvedGroup === group.group}
                                disabled={disabled}
                                onClick={() => setActiveGroup(group.group)}
                            />
                        );
                    })}
                </nav>

                <div className="h-64 overflow-y-auto md:h-72">
                    {visibleGroups.length === 0 ? (
                        <p className="text-muted-foreground px-3 py-10 text-center text-sm">
                            No permissions match “{query}”.
                        </p>
                    ) : (
                        visibleGroups.map((group) => {
                            const values = group.permissions.map(
                                (p) => p.value,
                            );
                            const state = groupState(values);
                            const granted = values.filter((value) =>
                                selectedSet.has(value),
                            ).length;

                            return (
                                <section key={group.group}>
                                    <div className="bg-muted/40 sticky top-0 z-10 flex items-center gap-2 border-b px-3 py-1.5">
                                        <TriStateCheckbox
                                            state={state}
                                            disabled={disabled}
                                            onToggle={() => toggleGroup(values)}
                                            label={`Toggle ${group.group}`}
                                        />
                                        <span className="min-w-0 flex-1 truncate text-xs font-medium">
                                            {group.group}
                                        </span>
                                        <span className="text-muted-foreground text-xs tabular-nums">
                                            {granted}/{values.length}
                                        </span>
                                    </div>
                                    <ul>
                                        {group.permissions.map(
                                            (permission) => {
                                                const checked =
                                                    selectedSet.has(
                                                        permission.value,
                                                    );

                                                return (
                                                    <li key={permission.value}>
                                                        <label
                                                            htmlFor={`permission-${permission.value}`}
                                                            className={cn(
                                                                'flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm',
                                                                checked
                                                                    ? 'bg-primary/5'
                                                                    : 'hover:bg-muted/40',
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
                                                            <span className="min-w-0 truncate">
                                                                {
                                                                    permission.label
                                                                }
                                                            </span>
                                                        </label>
                                                    </li>
                                                );
                                            },
                                        )}
                                    </ul>
                                </section>
                            );
                        })
                    )}
                </div>
            </div>
        </div>
    );
}

function GroupTab({
    label,
    count,
    active,
    disabled,
    onClick,
}: {
    label: string;
    count: string;
    active: boolean;
    disabled: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'flex shrink-0 items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-left text-sm whitespace-nowrap md:rounded-none md:px-3',
                active
                    ? 'bg-muted font-medium'
                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                disabled && 'cursor-not-allowed opacity-60',
            )}
        >
            <span className="truncate">{label}</span>
            <span className="text-muted-foreground text-xs tabular-nums">
                {count}
            </span>
        </button>
    );
}

function TriStateCheckbox({
    state,
    onToggle,
    disabled,
    label,
}: {
    state: GroupState;
    onToggle: () => void;
    disabled?: boolean;
    label: string;
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
            aria-label={label}
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
