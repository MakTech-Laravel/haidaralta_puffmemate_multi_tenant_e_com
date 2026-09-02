import { Head, usePage } from '@inertiajs/react';
import { usePermission } from '@/hooks/use-permissions';
import type { Auth } from '@/types';

export default function PlatformDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { hasRole, roles } = usePermission();
    const user = auth.user;

    return (
        <>
            <Head title="Platform Dashboard" />
            <div className="flex flex-col gap-2 p-2">
                <h1 className="text-2xl font-semibold">
                    Welcome, {user?.name}
                </h1>
                {hasRole('super-admin') && (
                    <p className="text-muted-foreground text-sm">
                        Role: {roles.join(', ')}
                    </p>
                )}
            </div>
        </>
    );
}

PlatformDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/platform/dashboard',
        },
    ],
};
