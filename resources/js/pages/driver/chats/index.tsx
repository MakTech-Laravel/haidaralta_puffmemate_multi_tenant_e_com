import { Head } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import Heading from '@/components/heading';

export default function DriverChatsIndex() {
    return (
        <>
            <Head title="Chats" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Chats"
                    description="Order threads with the branch and customer"
                />
                <DriverEmptyState
                    icon={MessageCircle}
                    title="No chats yet"
                    description="When a delivery needs a message, the order conversation will open here."
                />
            </div>
        </>
    );
}

DriverChatsIndex.layout = {
    breadcrumbs: [{ title: 'Chats', href: '/driver/chats' }],
};
