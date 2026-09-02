import { Head, usePage } from '@inertiajs/react';
import { useBranch } from '@/hooks/use-branch';
import { usePermission } from '@/hooks/use-permissions';
import type { Auth } from '@/types';

export default function BranchDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const branch = useBranch();
    const { hasRole, roles } = usePermission();
    const user = auth.user;

    return (
        <>
            <Head title="Branch Dashboard" />
            <div className="flex flex-col gap-2 p-2">
                <h1 className="text-2xl font-semibold">
                    Welcome, {user?.name}
                </h1>
                <p className="text-muted-foreground text-sm">
                    Branch: {branch.name} ({branch.slug})
                </p>
                {hasRole('super-admin') && (
                    <p className="text-muted-foreground text-sm">
                        Role: {roles.join(', ')}
                    </p>
                )}
            </div>
        </>
    );
}

BranchDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/branch/downtown/dashboard',
        },
    ],
};
