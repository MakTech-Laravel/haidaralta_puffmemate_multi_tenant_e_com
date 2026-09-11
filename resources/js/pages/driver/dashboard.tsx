import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2, MapPin, Navigation, Phone, Wallet } from 'lucide-react';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { Auth } from '@/types';

const stops = [
    {
        stop: '1',
        name: 'Elena V.',
        address: '418 Pine St · Apt 4',
        window: '10:15–10:35',
        status: 'Next',
    },
    {
        stop: '2',
        name: 'Chris T.',
        address: '90 Harbor Ave',
        window: '10:40–11:00',
        status: 'Queued',
    },
    {
        stop: '3',
        name: 'Priya N.',
        address: '12 Market Row',
        window: '11:10–11:30',
        status: 'Queued',
    },
    {
        stop: '4',
        name: 'James O.',
        address: '7 West Dock',
        window: '11:40–12:00',
        status: 'Queued',
    },
];

export default function DriverDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    return (
        <>
            <Head title="Driver Dashboard" />
            <div className="flex flex-col gap-5 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Welcome, {user?.name}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Today&apos;s route, earnings, and delivery status.
                        </p>
                    </div>
                    <DemoDataBadge />
                </div>

                <Card className="gap-4 border-2 py-5 shadow-none">
                    <CardHeader className="px-5">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge>Next stop</Badge>
                            <Badge variant="outline">ETA 8 min</Badge>
                        </div>
                        <CardTitle className="text-xl">
                            Elena V. · 418 Pine St
                        </CardTitle>
                        <CardDescription>
                            Apt 4 · leave at door · 2 bags · prepaid
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-5">
                        <div className="bg-muted h-2 overflow-hidden rounded-full">
                            <div className="bg-primary h-full w-2/5" />
                        </div>
                        <p className="text-muted-foreground mt-2 text-xs">
                            2 of 8 stops complete
                        </p>
                    </CardContent>
                    <CardFooter className="flex flex-wrap gap-2 px-5">
                        <Button type="button">
                            <Navigation data-icon="inline-start" />
                            Navigate
                        </Button>
                        <Button type="button" variant="outline">
                            <Phone data-icon="inline-start" />
                            Call customer
                        </Button>
                        <Button type="button" variant="secondary">
                            <CheckCircle2 data-icon="inline-start" />
                            Mark delivered
                        </Button>
                    </CardFooter>
                </Card>

                <div className="grid gap-3 sm:grid-cols-3">
                    <DemoStatCard
                        icon={MapPin}
                        label="Stops left"
                        value="6"
                        hint="8 assigned this shift"
                    />
                    <DemoStatCard
                        icon={Wallet}
                        label="Earnings today"
                        value="$94.50"
                        hint="Includes $12 tips"
                    />
                    <DemoStatCard
                        icon={CheckCircle2}
                        label="On-time"
                        value="100%"
                        hint="Both drops so far"
                    />
                </div>

                <Card className="gap-4 py-5 shadow-none">
                    <CardHeader className="px-5">
                        <CardTitle>Today&apos;s route</CardTitle>
                        <CardDescription>
                            Placeholder sequence from the Downtown depot.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 px-5">
                        {stops.map((stop, index) => (
                            <div key={stop.stop}>
                                {index > 0 ? (
                                    <Separator className="mb-3" />
                                ) : null}
                                <div className="flex items-start gap-3">
                                    <div className="bg-secondary flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold">
                                        {stop.stop}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {stop.name}
                                            </p>
                                            <Badge
                                                variant={
                                                    stop.status === 'Next'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {stop.status}
                                            </Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {stop.address}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            Window {stop.window}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Button type="button" size="lg">
                        Start next leg
                    </Button>
                    <Button variant="outline" size="lg" asChild>
                        <Link href="/driver/activity" prefetch>
                            My activity
                        </Link>
                    </Button>
                </div>
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
