import { Head, Link, setLayoutProps } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
import OrderAssignController from '@/actions/App/Http/Controllers/Branch/OrderAssignController';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type HistoryRow = {
    id: number;
    from_driver_id: number | null;
    from_driver_name: string | null;
    from_driver_phone: string | null;
    to_driver_id: number;
    to_driver_name: string;
    to_driver_phone: string | null;
    changed_by_name: string | null;
    reason: string | null;
    created_at: string;
};

type Branch = { id: number; name: string; slug: string };

function DriverNameLink({
    branchSlug,
    driverId,
    name,
}: {
    branchSlug: string;
    driverId: number | null;
    name: string | null;
}) {
    if (name === null) {
        return <span>—</span>;
    }

    if (driverId === null) {
        return <span className="font-medium">{name}</span>;
    }

    return (
        <Link
            href={DriverController.show.url([branchSlug, driverId])}
            className="text-primary font-medium underline-offset-4 hover:underline"
        >
            {name}
        </Link>
    );
}

export default function DriverAssignmentHistoryPage({
    branch,
    fulfillment,
    history,
}: {
    branch: Branch;
    fulfillment: { id: number; fulfillmentNumber: string };
    history: HistoryRow[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Orders', href: `/branch/${branch.slug}/orders` },
            {
                title: fulfillment.fulfillmentNumber,
                href: OrderController.show.url([
                    branch.slug,
                    fulfillment.id,
                ]),
            },
            {
                title: 'Driver assignment history',
                href: OrderAssignController.history.url([
                    branch.slug,
                    fulfillment.id,
                ]),
            },
        ],
    });

    return (
        <>
            <Head
                title={`Driver history · ${fulfillment.fulfillmentNumber}`}
            />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Driver assignment history"
                        description={fulfillment.fulfillmentNumber}
                    />
                    <Button variant="outline" asChild>
                        <Link
                            href={OrderController.show.url([
                                branch.slug,
                                fulfillment.id,
                            ])}
                        >
                            Back to order
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Changes</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {history.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No driver assignment changes yet.
                            </p>
                        ) : (
                            history.map((row) => (
                                <div
                                    key={row.id}
                                    className="space-y-2 rounded-md border p-3 text-sm"
                                >
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Previous driver
                                            </p>
                                            <DriverNameLink
                                                branchSlug={branch.slug}
                                                driverId={row.from_driver_id}
                                                name={row.from_driver_name}
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {row.from_driver_phone ?? '—'}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Current / assigned driver
                                            </p>
                                            <DriverNameLink
                                                branchSlug={branch.slug}
                                                driverId={row.to_driver_id}
                                                name={row.to_driver_name}
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {row.to_driver_phone ?? '—'}
                                            </p>
                                        </div>
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        {row.changed_by_name
                                            ? `By ${row.changed_by_name}`
                                            : 'By system'}{' '}
                                        · {row.created_at}
                                    </p>
                                    {row.reason ? (
                                        <p className="text-muted-foreground text-xs">
                                            Reason: {row.reason}
                                        </p>
                                    ) : null}
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
