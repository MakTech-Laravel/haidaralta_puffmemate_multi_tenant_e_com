import { Head } from '@inertiajs/react';
import { DriverLiveBoard } from '@/components/admin/driver-live-board';
import type { LiveBoard } from '@/components/admin/driver-live-board';
import Heading from '@/components/heading';

type Props = {
    board: LiveBoard;
    channels: string[];
    watchUrl: string;
};

export default function PlatformDriversLive({
    board,
    channels,
    watchUrl,
}: Props) {
    return (
        <>
            <Head title="Live tracking" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Live tracking"
                    description="Drivers on duty across all branches. Filter by branch, or watch everyone at once."
                />

                <DriverLiveBoard
                    board={board}
                    channels={channels}
                    watchUrl={watchUrl}
                    showBranchFilter
                />
            </div>
        </>
    );
}

PlatformDriversLive.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Live tracking', href: '/platform/driver-map' },
    ],
};
