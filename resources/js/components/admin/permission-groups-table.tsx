import { Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export type PermissionGroupItem = {
    value: string;
    label: string;
    roles_count: number;
};

export type PermissionGroup = {
    group: string;
    permissions: PermissionGroupItem[];
};

/**
 * Grouped permission catalog (client search). Intentional exception to
 * Eloquent *DataTable — Spatie permission keys are a static seeded catalog.
 */
export function PermissionGroupsTable({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    const [search, setSearch] = useState('');
    const [activeGroup, setActiveGroup] = useState<string | null>(
        permissionGroups[0]?.group ?? null,
    );

    const totalPermissions = useMemo(
        () =>
            permissionGroups.reduce(
                (sum, group) => sum + group.permissions.length,
                0,
            ),
        [permissionGroups],
    );

    const filteredGroups = useMemo(() => {
        const q = search.trim().toLowerCase();

        if (q === '') {
            return permissionGroups;
        }

        return permissionGroups
            .map((group) => ({
                ...group,
                permissions: group.permissions.filter(
                    (permission) =>
                        permission.label.toLowerCase().includes(q) ||
                        permission.value.toLowerCase().includes(q) ||
                        group.group.toLowerCase().includes(q),
                ),
            }))
            .filter((group) => group.permissions.length > 0);
    }, [permissionGroups, search]);

    const resolvedGroup =
        activeGroup !== null &&
        filteredGroups.some((group) => group.group === activeGroup)
            ? activeGroup
            : null;

    const visibleGroups =
        resolvedGroup === null
            ? filteredGroups
            : filteredGroups.filter((group) => group.group === resolvedGroup);

    const visibleCount = visibleGroups.reduce(
        (sum, group) => sum + group.permissions.length,
        0,
    );

    return (
        <div className="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div className="flex flex-col gap-2 border-b px-4 py-3 sm:flex-row sm:items-center">
                <div className="relative min-w-0 flex-1">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search permissions…"
                        className="h-8 pl-8"
                        autoComplete="off"
                    />
                </div>
                <p className="text-muted-foreground shrink-0 text-xs tabular-nums">
                    {visibleCount}/{totalPermissions} permissions ·{' '}
                    {filteredGroups.length} groups
                </p>
            </div>

            <div className="flex flex-col md:h-128 md:flex-row">
                <nav
                    aria-label="Permission groups"
                    className="flex shrink-0 gap-1 overflow-x-auto border-b p-1.5 md:w-48 md:flex-col md:overflow-y-auto md:overflow-x-hidden md:border-r md:border-b-0 md:p-1"
                >
                    <GroupTab
                        label="All"
                        count={`${totalPermissions}`}
                        active={resolvedGroup === null}
                        onClick={() => setActiveGroup(null)}
                    />
                    {filteredGroups.map((group) => (
                        <GroupTab
                            key={group.group}
                            label={group.group}
                            count={`${group.permissions.length}`}
                            active={resolvedGroup === group.group}
                            onClick={() => setActiveGroup(group.group)}
                        />
                    ))}
                </nav>

                <div className="min-w-0 flex-1 overflow-y-auto">
                    {visibleCount === 0 ? (
                        <p className="text-muted-foreground px-4 py-12 text-center text-sm">
                            {search.trim() === ''
                                ? 'No permissions available.'
                                : `No permissions match “${search}”.`}
                        </p>
                    ) : (
                        visibleGroups.map((group) => (
                            <section key={group.group}>
                                {resolvedGroup === null ? (
                                    <div className="bg-muted flex items-center justify-between gap-3 px-4 py-1.5">
                                        <h2 className="text-xs font-medium">
                                            {group.group}
                                        </h2>
                                        <span className="text-muted-foreground text-xs tabular-nums">
                                            {group.permissions.length}
                                        </span>
                                    </div>
                                ) : null}
                                <ul>
                                    {group.permissions.map((permission) => (
                                        <li
                                            key={permission.value}
                                            className="flex items-center gap-3 border-b px-4 py-2 last:border-b-0"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium">
                                                    {permission.label}
                                                </p>
                                                <p className="text-muted-foreground truncate font-mono text-xs">
                                                    {permission.value}
                                                </p>
                                            </div>
                                            <span className="text-muted-foreground shrink-0 text-xs tabular-nums">
                                                {permission.roles_count === 1
                                                    ? '1 role'
                                                    : `${permission.roles_count} roles`}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))
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
    onClick,
}: {
    label: string;
    count: string;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex w-full min-w-0 items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-left text-sm',
                active
                    ? 'bg-muted font-medium'
                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
            )}
        >
            <span className="min-w-0 truncate">{label}</span>
            <span className="text-muted-foreground shrink-0 text-xs tabular-nums">
                {count}
            </span>
        </button>
    );
}
