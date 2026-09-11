import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Building2,
    Radio,
    Server,
    TriangleAlert,
} from 'lucide-react';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermission } from '@/hooks/use-permissions';
import type { Auth } from '@/types';

const branches = [
    { name: 'Downtown', orders: '142', staff: '18', status: 'Healthy' },
    { name: 'Uptown', orders: '96', staff: '12', status: 'Healthy' },
    { name: 'Harbor', orders: '61', staff: '9', status: 'Watch' },
    { name: 'Westside', orders: '38', staff: '7', status: 'Healthy' },
];

const activity = [
    {
        time: '09:14',
        actor: 'Platform Super Admin',
        event: 'Updated branch hours',
        target: 'Harbor',
    },
    {
        time: '08:52',
        actor: 'System',
        event: 'Queue retry succeeded',
        target: 'scout',
    },
    {
        time: '08:31',
        actor: 'Maya Chen',
        event: 'Created staff account',
        target: 'Downtown',
    },
    {
        time: '07:58',
        actor: 'System',
        event: 'Backup completed',
        target: 'daily',
    },
];

export default function PlatformDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const { hasRole, roles } = usePermission();
    const user = auth.user;

    return (
        <>
            <Head title="Platform Dashboard" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <p className="text-muted-foreground text-[11px] font-medium tracking-[0.18em] uppercase">
                            Operations
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Welcome, {user?.name}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Command center for branches, queues, and system
                            health.
                        </p>
                        {hasRole('super-admin') && (
                            <p className="text-muted-foreground text-sm">
                                Role: {roles.join(', ')}
                            </p>
                        )}
                    </div>
                    <DemoDataBadge />
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <DemoStatCard
                        className="gap-2 py-3"
                        icon={Building2}
                        label="Active branches"
                        value="12"
                        hint="4 reporting this hour"
                    />
                    <DemoStatCard
                        className="gap-2 py-3"
                        icon={Activity}
                        label="Orders today"
                        value="337"
                        hint="+8% vs yesterday"
                    />
                    <DemoStatCard
                        className="gap-2 py-3"
                        icon={TriangleAlert}
                        label="Open tickets"
                        value="6"
                        hint="2 marked urgent"
                    />
                    <DemoStatCard
                        className="gap-2 py-3"
                        icon={Radio}
                        label="Queue depth"
                        value="14"
                        hint="Scout + default"
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-5">
                    <Card className="gap-4 py-5 shadow-none xl:col-span-3">
                        <CardHeader className="flex flex-row items-start justify-between gap-3 px-5">
                            <div>
                                <CardTitle className="text-base">
                                    Branches
                                </CardTitle>
                                <CardDescription>
                                    Snapshot of store throughput this morning.
                                </CardDescription>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/platform/branches" prefetch>
                                    View branches
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="px-5">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Branch</TableHead>
                                        <TableHead>Orders</TableHead>
                                        <TableHead>Staff</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {branches.map((row) => (
                                        <TableRow key={row.name}>
                                            <TableCell className="font-medium">
                                                {row.name}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {row.orders}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {row.staff}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        row.status === 'Watch'
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {row.status}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card className="gap-4 py-5 shadow-none xl:col-span-2">
                        <CardHeader className="px-5">
                            <CardTitle className="text-base">
                                System health
                            </CardTitle>
                            <CardDescription>
                                Placeholder checks — not live telemetry.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3 px-5">
                            {[
                                {
                                    label: 'Horizon workers',
                                    value: 'Ready',
                                    icon: Activity,
                                },
                                {
                                    label: 'Pulse ingest',
                                    value: 'Receiving',
                                    icon: Server,
                                },
                                {
                                    label: 'Meilisearch',
                                    value: 'Indexed',
                                    icon: Radio,
                                },
                                {
                                    label: 'Redis',
                                    value: 'Connected',
                                    icon: Radio,
                                },
                            ].map((item) => (
                                <div
                                    key={item.label}
                                    className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2"
                                >
                                    <div className="flex items-center gap-2 text-sm">
                                        <item.icon className="text-muted-foreground size-4" />
                                        {item.label}
                                    </div>
                                    <Badge variant="secondary">
                                        {item.value}
                                    </Badge>
                                </div>
                            ))}
                            <Separator />
                            <div className="flex flex-col gap-2">
                                <p className="text-muted-foreground text-xs">
                                    Live feed
                                </p>
                                <Skeleton className="h-3 w-3/4" />
                                <Skeleton className="h-3 w-1/2" />
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/platform/observability" prefetch>
                                    Open observability
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card className="gap-4 py-5 shadow-none">
                    <CardHeader className="flex flex-row items-start justify-between gap-3 px-5">
                        <div>
                            <CardTitle className="text-base">
                                Recent activity
                            </CardTitle>
                            <CardDescription>
                                Demo audit trail for this morning.
                            </CardDescription>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/platform/activity-logs" prefetch>
                                Activity log
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="px-5">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Time</TableHead>
                                    <TableHead>Actor</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Target</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {activity.map((row) => (
                                    <TableRow key={`${row.time}-${row.event}`}>
                                        <TableCell className="text-muted-foreground tabular-nums">
                                            {row.time}
                                        </TableCell>
                                        <TableCell>{row.actor}</TableCell>
                                        <TableCell>{row.event}</TableCell>
                                        <TableCell>{row.target}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
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
