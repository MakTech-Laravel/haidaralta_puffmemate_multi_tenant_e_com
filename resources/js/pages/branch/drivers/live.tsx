import { Head, setLayoutProps } from '@inertiajs/react';
import { DriverLiveBoard } from '@/components/admin/driver-live-board';
import type { LiveBoard } from '@/components/admin/driver-live-board';
import Heading from '@/components/heading';

type Props = {
    branch: { id: number; name: string; slug: string };
    board: LiveBoard;
    channels: string[];
    watchUrl: string;
};

export default function BranchDriversLive({
    branch,
    board,
    channels,
    watchUrl,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: `/branch/${branch.slug}/dashboard` },
            {
                title: 'Live tracking',
                href: `/branch/${branch.slug}/driver-map`,
            },
        ],
    });

    return (
        <>
            <Head title="Live tracking" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Live tracking"
                    description="Drivers on duty for this branch. Positions update live while this page is open."
                />

                <DriverLiveBoard
                    board={board}
                    channels={channels}
                    watchUrl={watchUrl}
                />
            </div>
        </>
    );
}
