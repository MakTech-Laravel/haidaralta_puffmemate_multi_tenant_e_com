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
import type { ConversationMessage, MessageAttachment } from './types';

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
}: {
    message: ConversationMessage;
    isOwn: boolean;
    showHeader: boolean;
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
                <Bubble variant={isOwn ? 'default' : 'muted'}>
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
                <MessageFooter>{formatTime(message.created_at)}</MessageFooter>
            </MessageContent>
        </Message>
    );
}
