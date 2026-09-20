import { Head } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import Heading from '@/components/heading';

export default function DriverNotificationsIndex({
    unreadCount = 0,
}: {
    unreadCount?: number;
}) {
    return (
        <>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Notifications"
                    description={
                        unreadCount > 0
                            ? `${unreadCount} unread`
                            : 'Stop assignments and delivery alerts'
                    }
                />
                <DriverEmptyState
                    icon={Bell}
                    title="You're all caught up"
                    description="New stops, OTP reminders, and branch messages will land here."
                />
            </div>
        </>
    );
}

DriverNotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifications', href: '/driver/notifications' }],
};
