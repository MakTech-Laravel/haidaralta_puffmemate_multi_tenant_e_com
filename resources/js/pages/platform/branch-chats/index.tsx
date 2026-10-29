import { Head } from '@inertiajs/react';

import BranchChatController from '@/actions/App/Http/Controllers/Platform/BranchChatController';
import { ConversationWorkspace } from '@/components/conversation';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
} from '@/components/conversation/types';

/**
 * Shared platform↔branch ops inbox (no order / customer / driver).
 */
export default function PlatformBranchChats({
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
    return (
        <>
            <Head
                title={
                    thread
                        ? (thread.conversation.branch?.name ?? 'Branch chat')
                        : 'Branch chat'
                }
            />

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
                    title="Branch chat"
                    showSenderRole
                    optionField="branch_id"
                    orderOptionsUrl={BranchChatController.branchOptions.url()}
                    storeUrl={BranchChatController.store.url()}
                    hrefFor={(row) => BranchChatController.show.url(row.id)}
                    deliveredUrlFor={(id) =>
                        BranchChatController.delivered.url(id)
                    }
                    threadTitle={
                        thread?.conversation.branch?.name ?? undefined
                    }
                />
            </div>
        </>
    );
}

PlatformBranchChats.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch chat', href: '/platform/branch-chats' },
    ],
};
