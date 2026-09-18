import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import {
    Clock,
    Package,
    ShoppingBag,
    TriangleAlert,
    Users,
} from 'lucide-react';
import { StatCard } from '@/components/portal/stat-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useBranch } from '@/hooks/use-branch';
import { useInitials } from '@/hooks/use-initials';
import { usePermission } from '@/hooks/use-permissions';
import type { Auth } from '@/types';

const orders = [
    {
        id: '#1842',
        customer: 'Priya N.',
        channel: 'Pickup',
        total: '$42.80',
        status: 'Ready',
    },
    {
        id: '#1841',
        customer: 'James O.',
        channel: 'Delivery',
        total: '$28.10',
        status: 'Packing',
    },
    {
        id: '#1840',
        customer: 'Elena V.',
        channel: 'Pickup',
        total: '$61.40',
        status: 'Paid',
    },
    {
        id: '#1839',
        customer: 'Chris T.',
        channel: 'Delivery',
        total: '$19.25',
        status: 'Out',
    },
];

const staff = [
    { name: 'Amina Haddad', role: 'Lead', until: '16:00' },
    { name: 'Noah Patel', role: 'Register', until: '14:00' },
    { name: 'Sofia Alvarez', role: 'Fulfillment', until: '21:00' },
    { name: 'Liam Brooks', role: 'Floor', until: '18:00' },
];

const alerts = [
    { sku: 'PM-ROSE-12', name: 'Rose 12-pack', level: '3 left' },
    { sku: 'PM-MINT-6', name: 'Mint sleeves', level: 'Low' },
    { sku: 'PM-BAGS', name: 'Pickup bags', level: 'Reorder' },
];

export default function BranchDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const branch = useBranch();
    const { hasRole, roles } = usePermission();
    const user = auth.user;
    const initials = useInitials();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
        ],
    });

    return (
        <>
            <Head title="Branch Dashboard" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
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
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={ShoppingBag}
                        label="Sales today"
                        value="$1,284"
                        hint="86 tickets so far"
                    />
                    <StatCard
                        icon={Package}
                        label="Open orders"
                        value="23"
                        hint="9 pickup · 14 delivery"
                    />
                    <StatCard
                        icon={Users}
                        label="On shift"
                        value="8"
                        hint="2 arriving at 14:00"
                    />
                    <StatCard
                        icon={TriangleAlert}
                        label="Inventory alerts"
                        value="3"
                        hint="Needs a restock walk"
                    />
                </div>

                <Alert>
                    <Clock />
                    <AlertTitle>Lunch rush window</AlertTitle>
                    <AlertDescription>
                        Placeholder reminder: 11:30–13:30 usually peaks. Keep
                        two people on packing.
                    </AlertDescription>
                </Alert>

                <div className="grid gap-4 lg:grid-cols-5">
                    <Card className="gap-4 py-5 shadow-none lg:col-span-3">
                        <CardHeader className="px-5">
                            <CardTitle>Today&apos;s orders</CardTitle>
                            <CardDescription>
                                Latest tickets on the {branch.name} floor.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="px-5">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Ticket</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Channel</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="font-medium">
                                                {order.id}
                                            </TableCell>
                                            <TableCell>
                                                {order.customer}
                                            </TableCell>
                                            <TableCell>
                                                {order.channel}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {order.total}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {order.status}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card className="gap-4 py-5 shadow-none lg:col-span-2">
                        <CardHeader className="flex flex-row items-start justify-between gap-3 px-5">
                            <div>
                                <CardTitle>Staff on shift</CardTitle>
                                <CardDescription>
                                    Who is on the floor right now.
                                </CardDescription>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={`/branch/${branch.slug}/staff`}
                                    prefetch
                                >
                                    Staff
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3 px-5">
                            {staff.map((member) => (
                                <div
                                    key={member.name}
                                    className="flex items-center gap-3"
                                >
                                    <Avatar>
                                        <AvatarFallback>
                                            {initials(member.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">
                                            {member.name}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {member.role}
                                        </p>
                                    </div>
                                    <span className="text-muted-foreground text-xs tabular-nums">
                                        until {member.until}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card className="gap-4 py-5 shadow-none">
                    <CardHeader className="px-5">
                        <CardTitle>Inventory alerts</CardTitle>
                        <CardDescription>
                            Placeholder stock warnings for this branch.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 px-5 sm:grid-cols-3">
                        {alerts.map((alert) => (
                            <div
                                key={alert.sku}
                                className="flex flex-col gap-1 rounded-xl border p-4"
                            >
                                <Badge variant="outline" className="w-fit">
                                    {alert.level}
                                </Badge>
                                <p className="text-sm font-medium">
                                    {alert.name}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {alert.sku}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
