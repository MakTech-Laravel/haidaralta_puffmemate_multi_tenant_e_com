import { Head } from '@inertiajs/react';

import PlatformChatController from '@/actions/App/Http/Controllers/Branch/PlatformChatController';
import { ConversationWorkspace } from '@/components/conversation';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
} from '@/components/conversation/types';
import { useBranch } from '@/hooks/use-branch';

/**
 * Shared branch↔platform HQ ops inbox (no order / customer / driver).
 */
export default function BranchPlatformChats({
    conversations,
    inboxMeta,
    thread,
    viewerId,
    viewerName,
    indexUrl,
    inboxChannel,
}: {
    conversations: ConversationRow[];
    inboxMeta: InboxMeta;
    thread: ConversationThreadProps | null;
    viewerId: number;
    viewerName: string;
    indexUrl: string;
    inboxChannel: string;
}) {
    const branch = useBranch();

    return (
        <>
            <Head title={thread ? 'Platform' : 'Platform chat'} />

            <div className="h-[calc(100svh-6rem)] p-2 md:p-4">
                <ConversationWorkspace
                    conversations={conversations}
                    inboxMeta={inboxMeta}
                    thread={thread}
                    viewerId={viewerId}
                    viewerName={viewerName}
                    indexUrl={indexUrl}
                    inboxChannel={inboxChannel}
                    kind="platform_branch"
                    title="Platform chat"
                    showSenderRole
                    canClose={false}
                    hideInbox
                    optionField="branch_id"
                    orderOptionsUrl={PlatformChatController.platformOptions.url(
                        branch.slug,
                    )}
                    storeUrl={PlatformChatController.store.url(branch.slug)}
                    hrefFor={(row) =>
                        PlatformChatController.show.url([branch.slug, row.id])
                    }
                    deliveredUrlFor={(id) =>
                        PlatformChatController.delivered.url([
                            branch.slug,
                            id,
                        ])
                    }
                    threadTitle="Platform"
                />
            </div>
        </>
    );
}

BranchPlatformChats.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/branch/dashboard' },
        { title: 'Platform chat', href: '/branch/platform-chats' },
    ],
};
