import { Download, FileText, Info } from 'lucide-react';
import {
    Attachment,
    AttachmentAction,
    AttachmentActions,
    AttachmentContent,
    AttachmentDescription,
    AttachmentGroup,
    AttachmentMedia,
    AttachmentTitle,
} from '@/components/ui/attachment';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { Marker, MarkerContent, MarkerIcon } from '@/components/ui/marker';
import {
    Message,
    MessageAvatar,
    MessageContent,
    MessageFooter,
    MessageHeader,
} from '@/components/ui/message';
import { cn } from '@/lib/utils';
import { MessageReceipt } from './message-receipt';
import type {
    ConversationMessage,
    MessageAttachment,
    ReceiptState,
} from './types';

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function formatTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function AttachmentList({ attachments }: { attachments: MessageAttachment[] }) {
    return (
        <AttachmentGroup className="mt-2">
            {attachments.map((attachment) => (
                <Attachment key={attachment.id} size="sm">
                    <AttachmentMedia
                        variant={attachment.is_image ? 'image' : 'icon'}
                    >
                        {attachment.is_image ? (
                            <img src={attachment.url} alt={attachment.name} />
                        ) : (
                            <FileText />
                        )}
                    </AttachmentMedia>
                    <AttachmentContent>
                        <AttachmentTitle>{attachment.name}</AttachmentTitle>
                        <AttachmentDescription>
                            {formatSize(attachment.size)}
                        </AttachmentDescription>
                    </AttachmentContent>
                    <AttachmentActions>
                        <AttachmentAction asChild>
                            <a
                                href={attachment.url}
                                download
                                aria-label={`Download ${attachment.name}`}
                            >
                                <Download />
                            </a>
                        </AttachmentAction>
                    </AttachmentActions>
                </Attachment>
            ))}
        </AttachmentGroup>
    );
}

/**
 * One transcript row.
 *
 * System rows render as a centered marker; participant rows render as a bubble
 * that is red-filled for the viewer's own messages and muted for everyone else,
 * matching the app's red-fill-only brand rule.
 */
export function ConversationMessageRow({
    message,
    isOwn,
    showHeader,
    receipt,
    onRetry,
    onDiscard,
}: {
    message: ConversationMessage;
    isOwn: boolean;
    showHeader: boolean;
    receipt: ReceiptState | null;
    onRetry?: () => void;
    onDiscard?: () => void;
}) {
    if (message.type === 'system' || message.sender === null) {
        return (
            <Marker variant="separator">
                <MarkerIcon>
                    <Info />
                </MarkerIcon>
                <MarkerContent>{message.body}</MarkerContent>
            </Marker>
        );
    }

    const sender = message.sender;

    return (
        <Message align={isOwn ? 'end' : 'start'}>
            <MessageAvatar>
                {showHeader ? (
                    <Avatar>
                        {sender.avatar_url ? (
                            <AvatarImage
                                src={sender.avatar_url}
                                alt={sender.name}
                            />
                        ) : null}
                        <AvatarFallback>{initials(sender.name)}</AvatarFallback>
                    </Avatar>
                ) : null}
            </MessageAvatar>
            <MessageContent>
                {showHeader && !isOwn ? (
                    <MessageHeader>{sender.name}</MessageHeader>
                ) : null}
                <Bubble
                    variant={isOwn ? 'default' : 'muted'}
                    className={cn(receipt === 'sending' && 'opacity-70')}
                >
                    <BubbleContent>
                        {message.body ? (
                            <p className="break-words whitespace-pre-wrap">
                                {message.body}
                            </p>
                        ) : null}
                        {message.attachments.length > 0 ? (
                            <AttachmentList attachments={message.attachments} />
                        ) : null}
                    </BubbleContent>
                </Bubble>
                <MessageFooter>
                    <span className="inline-flex items-center gap-1">
                        {formatTime(message.created_at)}
                        {receipt ? <MessageReceipt state={receipt} /> : null}
                    </span>
                </MessageFooter>
                {receipt === 'failed' ? (
                    <div className="text-destructive flex items-center gap-2 text-xs">
                        <span>Not sent.</span>
                        <button
                            type="button"
                            onClick={onRetry}
                            className="font-medium underline underline-offset-2"
                        >
                            Retry
                        </button>
                        <button
                            type="button"
                            onClick={onDiscard}
                            className="text-muted-foreground underline underline-offset-2"
                        >
                            Discard
                        </button>
                    </div>
                ) : null}
            </MessageContent>
        </Message>
    );
}
