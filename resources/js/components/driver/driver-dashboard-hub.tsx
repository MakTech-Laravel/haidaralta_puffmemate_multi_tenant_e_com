import { Link } from '@inertiajs/react';
import {
    Bell,
    Bike,
    CloudRain,
    CircleHelp,
    Clock,
    MapPin,
    MessageCircle,
    Navigation,
    Package,
    Phone,
    Shield,
    Star,
    UserRound,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    driverAreaMapsQuery,
    driverOrderStatusLabel,
    type DriverDashboardHub,
    type DriverDashboardSummary,
} from '@/components/driver/driver-order-types';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { index as accountIndex } from '@/routes/driver/account';
import { index as earningsIndex } from '@/routes/driver/earnings';
import { index as notificationsIndex } from '@/routes/driver/notifications';
import {
    chat as orderChat,
    index as ordersIndex,
    show as orderShow,
} from '@/routes/driver/orders';
import { index as routeIndex } from '@/routes/driver/route';
import type { User } from '@/types';

function mapsSearchUrl(query: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
}

function demoOnly(message: string): void {
    toast.message(message, {
        description: 'Placeholder — this control is not live yet.',
    });
}

type DriverDashboardHubProps = {
    user: User | null;
    summary: DriverDashboardSummary;
    hub: DriverDashboardHub;
};

export function DriverDashboardHub({
    user,
    summary,
    hub,
}: DriverDashboardHubProps) {
    const getInitials = useInitials();
    const [online, setOnline] = useState(hub.online);
    const firstName = user?.name.split(/\s+/u)[0] ?? 'there';
    const nextStop = summary.nextStop;
    const currentArea = summary.currentArea;
    const assignedCount = summary.assignedCount;
    const progressPercent =
        assignedCount > 0
            ? Math.round((summary.deliveredCount / assignedCount) * 100)
            : 0;
    const areaProgressPercent =
        currentArea && currentArea.orderCount > 0
            ? Math.round(
                  (currentArea.deliveredCount / currentArea.orderCount) * 100,
              )
            : 0;

    const quickActions = [
        {
            id: 'route',
            label: 'Route',
            hint: 'Suburb sequence',
            href: routeIndex.url(),
            icon: Navigation,
        },
        {
            id: 'orders',
            label: 'Orders',
            hint: 'Assigned list',
            href: ordersIndex.url(),
            icon: Package,
        },
        {
            id: 'alerts',
            label: 'Alerts',
            hint: 'Stop updates',
            href: notificationsIndex.url(),
            icon: Bell,
        },
        {
            id: 'earnings',
            label: 'Earnings',
            hint: 'Pay and tips',
            href: earningsIndex.url(),
            icon: Wallet,
        },
        {
            id: 'account',
            label: 'Account',
            hint: 'Profile',
            href: accountIndex.url(),
            icon: UserRound,
        },
        {
            id: 'branch',
            label: 'Branch',
            hint: 'Call desk',
            href: `tel:${hub.branchPhone}`,
            icon: Phone,
            external: true,
        },
    ] as const;

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <p className="text-muted-foreground text-sm font-medium">
                        {hub.shift.window} · {hub.shift.branch}
                    </p>
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                        Hello, {firstName}
                    </h1>
                </div>
                <DemoDataBadge />
            </div>

            <Card className="gap-4 py-4 shadow-none">
                <CardContent className="flex flex-col gap-4 px-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex min-w-0 items-center gap-3">
                        <Avatar className="size-12">
                            {user?.avatar ? (
                                <AvatarImage src={user.avatar} alt={user.name} />
                            ) : null}
                            <AvatarFallback>
                                {getInitials(user?.name ?? 'Driver')}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 space-y-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="text-foreground truncate font-semibold">
                                    {user?.name ?? 'Driver'}
                                </p>
                                <Badge
                                    variant={online ? 'default' : 'secondary'}
                                >
                                    {online ? 'On shift' : 'Paused'}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground flex flex-wrap items-center gap-x-2 text-sm">
                                <Clock className="size-3.5" aria-hidden />
                                {hub.shift.label} · ends in {hub.shift.endsIn}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Switch
                            id="driver-accepting-stops"
                            checked={online}
                            onCheckedChange={(checked) => {
                                setOnline(checked);
                                demoOnly(
                                    checked
                                        ? 'Accepting new stops'
                                        : 'Paused — new stops held',
                                );
                            }}
                        />
                        <Label htmlFor="driver-accepting-stops">
                            {online ? 'Accepting stops' : 'Not accepting'}
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <div className="flex flex-col gap-3">
                {hub.alerts.map((alert) => (
                    <Alert key={alert.id}>
                        {alert.id === 'weather' ? (
                            <CloudRain />
                        ) : alert.id === 'area' ? (
                            <MapPin />
                        ) : (
                            <Shield />
                        )}
                        <AlertTitle>{alert.title}</AlertTitle>
                        <AlertDescription>{alert.body}</AlertDescription>
                    </Alert>
                ))}
            </div>

            {currentArea && nextStop ? (
                <Card className="ring-primary gap-4 py-4 shadow-none ring-2">
                    <CardHeader className="gap-2 px-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge>Current area</Badge>
                            <Badge variant="secondary">{hub.etaNext} to first drop</Badge>
                        </div>
                        <CardTitle className="text-lg leading-snug sm:text-xl">
                            {currentArea.label}
                        </CardTitle>
                        <CardDescription className="flex items-start gap-1.5 text-sm">
                            <MapPin className="mt-0.5 size-3.5 shrink-0" />
                            {currentArea.remainingCount} of {currentArea.orderCount}{' '}
                            {currentArea.orderCount === 1 ? 'drop' : 'drops'} still
                            in this suburb
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 px-4">
                        <div className="bg-muted/60 space-y-1 rounded-lg p-3">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                First drop
                            </p>
                            <p className="text-foreground font-medium">
                                {nextStop.customerName}
                            </p>
                            <p className="text-muted-foreground text-sm leading-snug">
                                {nextStop.addressSummary}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {nextStop.windowLabel} · {nextStop.productCount}{' '}
                                {nextStop.productCount === 1 ? 'item' : 'items'} ·{' '}
                                {nextStop.totalLabel}
                            </p>
                        </div>
                        {hub.nextNotes ? (
                            <p className="text-foreground text-sm leading-relaxed">
                                Note: {hub.nextNotes}
                            </p>
                        ) : null}
                        <div className="space-y-1.5">
                            <div className="text-muted-foreground flex items-center justify-between text-xs">
                                <span>{currentArea.label} progress</span>
                                <span>
                                    {currentArea.deliveredCount} of{' '}
                                    {currentArea.orderCount} delivered
                                </span>
                            </div>
                            <Progress
                                value={areaProgressPercent}
                                aria-label={`${currentArea.label} delivery progress`}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <div className="text-muted-foreground flex items-center justify-between text-xs">
                                <span>Shift progress</span>
                                <span>
                                    {summary.deliveredCount} of {assignedCount}{' '}
                                    delivered
                                </span>
                            </div>
                            <Progress
                                value={progressPercent}
                                aria-label="Shift delivery progress"
                            />
                        </div>
                    </CardContent>
                    <CardFooter className="grid grid-cols-2 gap-2 px-4">
                        <Button
                            type="button"
                            size="lg"
                            className="h-12"
                            asChild
                        >
                            <Link href={orderShow.url(nextStop.id)} prefetch>
                                <Navigation data-icon="inline-start" />
                                Open first drop
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            className="h-12"
                            asChild
                        >
                            <a
                                href={mapsSearchUrl(
                                    driverAreaMapsQuery(currentArea.label),
                                )}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <MapPin data-icon="inline-start" />
                                Navigate to area
                            </a>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            className="h-12"
                            asChild
                        >
                            <Link href={orderChat.url(nextStop.id)} prefetch>
                                <MessageCircle data-icon="inline-start" />
                                Chat
                            </Link>
                        </Button>
                        {hub.nextCustomerPhone ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                className="h-12"
                                asChild
                            >
                                <a href={`tel:${hub.nextCustomerPhone}`}>
                                    <Phone data-icon="inline-start" />
                                    Call
                                </a>
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                className="h-12"
                                disabled
                            >
                                <Phone data-icon="inline-start" />
                                Call
                            </Button>
                        )}
                    </CardFooter>
                </Card>
            ) : (
                <Card className="gap-3 py-4 shadow-none">
                    <CardHeader className="gap-1 px-4">
                        <CardTitle className="text-base">
                            No active stops
                        </CardTitle>
                        <CardDescription>
                            All assigned demo orders are delivered, or none are
                            queued.
                        </CardDescription>
                    </CardHeader>
                    <CardFooter className="px-4">
                        <Button size="lg" className="h-12 w-full" asChild>
                            <Link href={routeIndex.url()} prefetch>
                                View route
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            )}

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {[
                    {
                        icon: Wallet,
                        label: 'Today',
                        value: hub.earnings.todayLabel,
                        hint: `${hub.earnings.tipsLabel} tips`,
                        href: earningsIndex.url(),
                    },
                    {
                        icon: Star,
                        label: 'Rating',
                        value: hub.performance.rating,
                        hint: `${hub.performance.ratingCount} ratings`,
                    },
                    {
                        icon: MapPin,
                        label: 'Stops left',
                        value: String(summary.activeCount),
                        hint: `${summary.areaCount} ${summary.areaCount === 1 ? 'area' : 'areas'}`,
                    },
                    {
                        icon: Navigation,
                        label: 'Distance',
                        value: hub.kmToday,
                        hint: `${hub.performance.onTimePercent}% on time`,
                    },
                ].map((stat) => {
                    const Icon = stat.icon;
                    const card = (
                        <Card className="gap-2 py-4 shadow-none">
                            <CardContent className="space-y-2 px-4">
                                <div className="flex items-center gap-2">
                                    <span className="bg-muted flex size-8 items-center justify-center rounded-full">
                                        <Icon className="size-4" aria-hidden />
                                    </span>
                                    <p className="text-muted-foreground text-xs font-medium">
                                        {stat.label}
                                    </p>
                                </div>
                                <p className="text-foreground text-xl font-semibold tracking-tight">
                                    {stat.value}
                                </p>
                                <p className="text-muted-foreground text-xs leading-snug">
                                    {stat.hint}
                                </p>
                            </CardContent>
                        </Card>
                    );

                    if ('href' in stat && stat.href) {
                        return (
                            <Link
                                key={stat.label}
                                href={stat.href}
                                prefetch
                                className="block transition-opacity hover:opacity-95"
                            >
                                {card}
                            </Link>
                        );
                    }

                    return card;
                })}
            </div>

            <div>
                <h2 className="text-foreground mb-3 text-lg font-semibold">
                    Quick actions
                </h2>
                <div className="grid grid-cols-3 gap-2 sm:grid-cols-6">
                    {quickActions.map((action) => {
                        const Icon = action.icon;
                        const className = cn(
                            'border-border hover:bg-accent flex h-auto flex-col items-center gap-1 rounded-xl border px-2 py-3 text-center',
                        );

                        const inner = (
                            <>
                                <Icon className="size-5" aria-hidden />
                                <span className="text-foreground text-xs font-medium">
                                    {action.label}
                                </span>
                                <span className="text-muted-foreground hidden text-[11px] sm:block">
                                    {action.hint}
                                </span>
                            </>
                        );

                        if ('external' in action && action.external) {
                            return (
                                <a
                                    key={action.id}
                                    href={action.href}
                                    className={className}
                                >
                                    {inner}
                                </a>
                            );
                        }

                        return (
                            <Link
                                key={action.id}
                                href={action.href}
                                prefetch
                                className={className}
                            >
                                {inner}
                            </Link>
                        );
                    })}
                </div>
            </div>

            <Card className="gap-3 py-4 shadow-none">
                <CardHeader className="gap-1 px-4">
                    <CardTitle className="text-base">
                        {currentArea
                            ? `Drops in ${currentArea.label}`
                            : 'Upcoming stops'}
                    </CardTitle>
                    <CardDescription>
                        Same-suburb orders stay on your run. Finish this
                        neighbourhood before the next one.
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col px-4">
                    {summary.activeOrders.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No active orders right now.
                        </p>
                    ) : (
                        summary.activeOrders.map((order, index) => (
                            <div key={order.id}>
                                {index > 0 ? (
                                    <Separator className="my-3" />
                                ) : null}
                                <Link
                                    href={orderShow.url(order.id)}
                                    prefetch
                                    className="flex items-start gap-3 py-0.5"
                                >
                                    <div className="bg-secondary flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold">
                                        {index + 1}
                                    </div>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {order.customerName}
                                            </p>
                                            <Badge
                                                className="rounded-full"
                                                variant={
                                                    index === 0
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {index === 0
                                                    ? 'Next'
                                                    : driverOrderStatusLabel[
                                                          order.status
                                                      ]}
                                            </Badge>
                                        </div>
                                        <p className="text-muted-foreground text-sm leading-snug">
                                            {order.addressSummary}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {order.windowLabel} ·{' '}
                                            {order.totalLabel}
                                        </p>
                                    </div>
                                </Link>
                            </div>
                        ))
                    )}
                </CardContent>
                <CardFooter className="px-4">
                    <Button
                        variant="outline"
                        size="lg"
                        className="h-12 w-full"
                        asChild
                    >
                        <Link href={routeIndex.url()} prefetch>
                            Open today’s route
                        </Link>
                    </Button>
                </CardFooter>
            </Card>

            {summary.laterAreas.length > 0 ? (
                <Card className="gap-3 py-4 shadow-none">
                    <CardHeader className="gap-1 px-4">
                        <CardTitle className="text-base">Later areas</CardTitle>
                        <CardDescription>
                            Stay in the current suburb until these batches
                            start.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 px-4">
                        {summary.laterAreas.map((area) => (
                            <div
                                key={area.id}
                                className="border-border space-y-2 rounded-lg border p-3"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <p className="font-medium">{area.label}</p>
                                    <Badge variant="secondary">
                                        {area.remainingCount}{' '}
                                        {area.remainingCount === 1
                                            ? 'drop'
                                            : 'drops'}
                                    </Badge>
                                </div>
                                <p className="text-muted-foreground text-sm leading-snug">
                                    {area.remaining
                                        .map((order) => order.customerName)
                                        .join(' · ')}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            ) : null}

            <div className="grid gap-3 xl:grid-cols-2">
                <Card className="gap-3 py-4 shadow-none">
                    <CardHeader className="gap-1 px-4">
                        <CardTitle className="text-base">Earnings</CardTitle>
                        <CardDescription>
                            Totals for this shift and week.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 px-4 text-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Today</span>
                            <span className="font-semibold">
                                {hub.earnings.todayLabel}
                            </span>
                        </div>
                        <Separator />
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">
                                This week
                            </span>
                            <span className="font-medium">
                                {hub.earnings.weekLabel}
                            </span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Tips</span>
                            <span>{hub.earnings.tipsLabel}</span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">
                                Avg / stop
                            </span>
                            <span>{hub.earnings.perStopLabel}</span>
                        </div>
                    </CardContent>
                    <CardFooter className="px-4">
                        <Button
                            variant="outline"
                            size="lg"
                            className="h-12 w-full"
                            asChild
                        >
                            <Link href={earningsIndex.url()} prefetch>
                                View earnings
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>

                <Card className="gap-3 py-4 shadow-none">
                    <CardHeader className="gap-1 px-4">
                        <CardTitle className="text-base">Performance</CardTitle>
                        <CardDescription>
                            On-time, acceptance, and completion.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4 px-4">
                        {(
                            [
                                [
                                    'On time',
                                    hub.performance.onTimePercent,
                                ],
                                [
                                    'Acceptance',
                                    hub.performance.acceptancePercent,
                                ],
                                [
                                    'Completion',
                                    hub.performance.completionPercent,
                                ],
                            ] as const
                        ).map(([label, value]) => (
                            <div key={label} className="space-y-1.5">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {label}
                                    </span>
                                    <span className="font-medium">{value}%</span>
                                </div>
                                <Progress
                                    value={value}
                                    aria-label={`${label} ${value} percent`}
                                />
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>

            <Card className="gap-3 py-4 shadow-none">
                <CardHeader className="gap-1 px-4">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Bike className="size-4" aria-hidden />
                        Vehicle
                    </CardTitle>
                    <CardDescription>
                        Assigned bike and document status.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-2 px-4 text-sm">
                    <p className="text-foreground font-medium">
                        {hub.vehicle.name}
                    </p>
                    <p className="text-muted-foreground">{hub.vehicle.plate}</p>
                    <p className="text-muted-foreground">{hub.vehicle.fuel}</p>
                    <p className="text-muted-foreground">
                        Insurance until {hub.vehicle.insuranceUntil}
                    </p>
                    <Badge
                        variant={hub.vehicle.docsOk ? 'secondary' : 'outline'}
                    >
                        {hub.vehicle.docsOk
                            ? 'Documents in date'
                            : 'Documents need review'}
                    </Badge>
                </CardContent>
            </Card>

            <div className="grid grid-cols-2 gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="lg"
                    className="h-12"
                    onClick={() =>
                        demoOnly('Safety check-in recorded for this demo')
                    }
                >
                    <Shield data-icon="inline-start" />
                    Safety check-in
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="lg"
                    className="h-12"
                    asChild
                >
                    <a href={`tel:${hub.supportPhone}`}>
                        <CircleHelp data-icon="inline-start" />
                        Call support
                    </a>
                </Button>
            </div>
        </div>
    );
}
