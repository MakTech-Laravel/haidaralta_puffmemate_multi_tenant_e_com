import { Head } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';

export function NotificationsInbox() {
    return (
        <>
            <Head title="Notifications" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Notifications"
                    description="You have no notifications yet. Alerts for orders, staff, and system events will appear here."
                />

                <Card>
                    <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-12 text-center text-sm">
                        <Bell className="size-8 opacity-40" />
                        <p>No notifications</p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
