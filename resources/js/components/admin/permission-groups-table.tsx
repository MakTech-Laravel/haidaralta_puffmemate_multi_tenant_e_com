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

    const orderedGroups = useMemo(
        () =>
            [...permissionGroups].sort((a, b) =>
                a.group.localeCompare(b.group, undefined, {
                    numeric: true,
                    sensitivity: 'base',
                }),
            ),
        [permissionGroups],
    );

    const totals = useMemo(() => {
        const permissions = orderedGroups.flatMap((group) => group.permissions);

        return {
            permissions: permissions.length,
            groups: orderedGroups.length,
            assigned: permissions.filter((item) => item.roles_count > 0)
                .length,
        };
    }, [orderedGroups]);

    const filteredGroups = useMemo(() => {
        const q = search.trim().toLowerCase();

        if (q === '') {
            return orderedGroups;
        }

        return orderedGroups
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
    }, [orderedGroups, search]);

    return (
        <div className="flex flex-col gap-6">
            <dl className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <Stat label="Permissions" value={totals.permissions} />
                <Stat label="Groups" value={totals.groups} />
                <Stat label="Used by a role" value={totals.assigned} />
            </dl>

            <div className="relative max-w-md">
                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search by name, key, or group…"
                    className="pl-9"
                    autoComplete="off"
                />
            </div>

            {filteredGroups.length === 0 ? (
                <p className="text-muted-foreground rounded-2xl border border-dashed py-16 text-center text-sm">
                    {search.trim() === ''
                        ? 'No permissions available.'
                        : `No permissions match “${search}”.`}
                </p>
            ) : (
                <div className="columns-1 gap-4 md:columns-2 xl:columns-3">
                    {filteredGroups.map((group) => (
                        <article
                            key={group.group}
                            className="mb-4 w-full break-inside-avoid overflow-hidden rounded-2xl border bg-card shadow-sm"
                        >
                            <header className="flex items-center gap-3 border-b px-4 py-3">
                                <span
                                    aria-hidden
                                    className="bg-muted text-muted-foreground flex size-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold"
                                >
                                    {group.group.charAt(0)}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <h2 className="truncate text-sm font-semibold tracking-tight">
                                        {group.group}
                                    </h2>
                                    <p className="text-muted-foreground text-xs tabular-nums">
                                        {group.permissions.length === 1
                                            ? '1 permission'
                                            : `${group.permissions.length} permissions`}
                                    </p>
                                </div>
                            </header>
                            <ul>
                                {group.permissions.map((permission) => (
                                    <li
                                        key={permission.value}
                                        className="flex items-start justify-between gap-3 border-b px-4 py-2.5 last:border-b-0"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {permission.label}
                                            </p>
                                            <p className="text-muted-foreground mt-0.5 truncate font-mono text-xs">
                                                {permission.value}
                                            </p>
                                        </div>
                                        <span
                                            className={cn(
                                                'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums',
                                                permission.roles_count > 0
                                                    ? 'bg-primary/10 text-primary'
                                                    : 'bg-muted text-muted-foreground',
                                            )}
                                        >
                                            {permission.roles_count === 1
                                                ? '1 role'
                                                : `${permission.roles_count} roles`}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </article>
                    ))}
                </div>
            )}
        </div>
    );
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-2xl border bg-card px-4 py-3 shadow-sm">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="mt-1 text-2xl font-semibold tracking-tight tabular-nums">
                {value}
            </dd>
        </div>
    );
}
