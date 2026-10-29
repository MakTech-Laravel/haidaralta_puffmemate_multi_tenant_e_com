import { Lock, Paperclip, SendHorizonal, X } from 'lucide-react';
import { useRef, useState, type ChangeEvent, type KeyboardEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

const MAX_ATTACHMENTS = 5;

/**
 * The composer is deliberately never busy.
 *
 * `onSend` hands the message to the transcript as an optimistic row and returns
 * immediately, so the input clears at once and the sending, failed, and
 * delivered states are shown on the bubble where a messenger shows them.
 */
export function ConversationComposer({
    disabled,
    onSend,
    onTyping,
}: {
    disabled: boolean;
    onSend: (body: string, files: File[]) => boolean;
    onTyping: () => void;
}) {
    const [body, setBody] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const canSubmit = !disabled && (body.trim() !== '' || files.length > 0);

    const submit = () => {
        if (!canSubmit) {
            return;
        }

        if (onSend(body.trim(), files)) {
            setBody('');
            setFiles([]);
        }
    };

    const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submit();
        }
    };

    const onPickFiles = (event: ChangeEvent<HTMLInputElement>) => {
        const picked = Array.from(event.target.files ?? []);

        setFiles((current) =>
            [...current, ...picked].slice(0, MAX_ATTACHMENTS),
        );

        event.target.value = '';
    };

    if (disabled) {
        return (
            <div className="bg-muted/40 text-muted-foreground flex items-center justify-center gap-2 border-t px-4 py-6 text-sm">
                <Lock className="size-4" />
                This conversation is closed. Messages are read-only.
            </div>
        );
    }

    return (
        <div className="bg-background border-t p-3">
            {files.length > 0 ? (
                <div className="mb-2 flex flex-wrap gap-2">
                    {files.map((file, index) => (
                        <Badge
                            key={`${file.name}-${index}`}
                            variant="secondary"
                            className="gap-1 py-1"
                        >
                            <span className="max-w-[12rem] truncate">
                                {file.name}
                            </span>
                            <button
                                type="button"
                                aria-label={`Remove ${file.name}`}
                                onClick={() =>
                                    setFiles((current) =>
                                        current.filter((_, i) => i !== index),
                                    )
                                }
                            >
                                <X className="size-3" />
                            </button>
                        </Badge>
                    ))}
                </div>
            ) : null}

            <div className="flex items-end gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Attach files"
                    disabled={files.length >= MAX_ATTACHMENTS}
                    onClick={() => fileInputRef.current?.click()}
                >
                    <Paperclip />
                </Button>
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    className="hidden"
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                    onChange={onPickFiles}
                />
                <Textarea
                    value={body}
                    onChange={(event) => {
                        setBody(event.target.value);
                        onTyping();
                    }}
                    onKeyDown={onKeyDown}
                    rows={1}
                    maxLength={5000}
                    placeholder="Write a message…"
                    className="max-h-40 min-h-10 flex-1 resize-none"
                />
                <Button
                    type="button"
                    size="icon"
                    aria-label="Send message"
                    disabled={!canSubmit}
                    onClick={submit}
                >
                    <SendHorizonal />
                </Button>
            </div>
        </div>
    );
}
