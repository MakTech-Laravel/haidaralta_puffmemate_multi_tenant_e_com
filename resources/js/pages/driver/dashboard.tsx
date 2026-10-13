import { Head, usePage } from '@inertiajs/react';
import { DriverDashboardHub } from '@/components/driver/driver-dashboard-hub';
import type {
    DriverDashboardHub as DriverDashboardHubData,
    DriverDashboardSummary,
} from '@/components/driver/driver-order-types';
import { useDriverOrdersRealtime } from '@/hooks/use-driver-orders-realtime';
import { dashboard } from '@/routes/driver';
import type { Auth } from '@/types';

const realtimeProps = ['summary', 'hub'];

type DriverDashboardProps = {
    summary: DriverDashboardSummary;
    hub: DriverDashboardHubData;
};

export default function DriverDashboard({
    summary,
    hub,
}: DriverDashboardProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    useDriverOrdersRealtime(realtimeProps);

    return (
        <>
            <Head title="Driver Dashboard" />

            <DriverDashboardHub
                user={auth.user}
                summary={summary}
                hub={hub}
            />
        </>
    );
}

DriverDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard.url(),
        },
    ],
};
