import { Head } from '@inertiajs/react';
import { Activity, AlertTriangle, ExternalLink, Gauge } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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

type Props = {
    canViewHorizon: boolean;
    canViewPulse: boolean;
    horizonPath: string;
    pulsePath: string;
    queueConnection: string;
    horizonReady: boolean;
};

export default function PlatformObservabilityIndex({
    canViewHorizon,
    canViewPulse,
    horizonPath,
    pulsePath,
    queueConnection,
    horizonReady,
}: Props) {
    return (
        <>
            <Head title="Observability" />

            <div className="flex flex-col gap-6 p-2">
                <Heading
                    title="Observability"
                    description="Open Laravel Horizon for queues and Laravel Pulse for application metrics. These are the official package dashboards."
                />

                {!horizonReady && canViewHorizon ? (
                    <Alert>
                        <AlertTriangle />
                        <AlertTitle>Horizon needs Redis queues</AlertTitle>
                        <AlertDescription>
                            Current queue connection is{' '}
                            <Badge variant="secondary">{queueConnection}</Badge>.
                            Horizon only consumes Redis. Local Windows typically
                            uses database/sync; production Coolify should set{' '}
                            <code className="text-xs">QUEUE_CONNECTION=redis</code>.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <div className="grid gap-4 md:grid-cols-2">
                    {canViewHorizon ? (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Activity className="text-muted-foreground size-5" />
                                    <CardTitle>Horizon</CardTitle>
                                </div>
                                <CardDescription>
                                    Queue monitoring for Redis workers — throughput,
                                    pending and failed jobs, metrics, tags, pause /
                                    continue, and retries.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2 text-sm text-muted-foreground">
                                <p>
                                    Opens the official Horizon Vue dashboard in a new
                                    tab. Use Clear Queue only when you intend to drop
                                    pending jobs.
                                </p>
                            </CardContent>
                            <CardFooter>
                                <Button asChild>
                                    <a
                                        href={horizonPath}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Open Horizon
                                        <ExternalLink data-icon="inline-end" />
                                    </a>
                                </Button>
                            </CardFooter>
                        </Card>
                    ) : null}

                    {canViewPulse ? (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Gauge className="text-muted-foreground size-5" />
                                    <CardTitle>Pulse</CardTitle>
                                </div>
                                <CardDescription>
                                    Application health — servers, usage, exceptions,
                                    queues, slow requests/jobs/queries, outgoing
                                    HTTP, and cache.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2 text-sm text-muted-foreground">
                                <p>
                                    Opens the official Pulse Livewire dashboard in a
                                    new tab. Cards update while traffic and{' '}
                                    <code className="text-xs">pulse:check</code> run.
                                </p>
                            </CardContent>
                            <CardFooter>
                                <Button asChild>
                                    <a
                                        href={pulsePath}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Open Pulse
                                        <ExternalLink data-icon="inline-end" />
                                    </a>
                                </Button>
                            </CardFooter>
                        </Card>
                    ) : null}
                </div>

                <Separator />

                <p className="text-muted-foreground text-sm">
                    Access is limited to the platform portal. Platform super-admins
                    always have access; other roles need{' '}
                    <code className="text-xs">view-horizon</code> and/or{' '}
                    <code className="text-xs">view-pulse</code>.
                </p>
            </div>
        </>
    );
}

PlatformObservabilityIndex.layout = {
    breadcrumbs: [
        {
            title: 'Observability',
            href: '/platform/observability',
        },
    ],
};
