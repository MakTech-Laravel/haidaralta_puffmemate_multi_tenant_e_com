import { Head, usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

export default function DriverDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    return (
        <>
            <Head title="Driver Dashboard" />
            <div className="flex flex-col gap-2 p-2">
                <h1 className="text-2xl font-semibold">
                    Welcome, {user?.name}
                </h1>
            </div>
        </>
    );
}

DriverDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/driver/dashboard',
        },
    ],
};
