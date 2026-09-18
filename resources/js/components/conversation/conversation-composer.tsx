import { Lock, Paperclip, SendHorizonal, X } from 'lucide-react';
import { useRef, useState, type ChangeEvent, type KeyboardEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

const MAX_ATTACHMENTS = 5;

export function ConversationComposer({
    disabled,
    sending,
    onSend,
    onTyping,
}: {
    disabled: boolean;
    sending: boolean;
    onSend: (body: string, files: File[]) => Promise<boolean>;
    onTyping: () => void;
}) {
    const [body, setBody] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const canSubmit =
        !disabled && !sending && (body.trim() !== '' || files.length > 0);

    const submit = async () => {
        if (!canSubmit) {
            return;
        }

        const sent = await onSend(body, files);

        if (sent) {
            setBody('');
            setFiles([]);
        }
    };

    const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            void submit();
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
                    disabled={sending || files.length >= MAX_ATTACHMENTS}
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
                    placeholder="Write a message…  (Enter to send, Shift+Enter for a new line)"
                    className="max-h-40 min-h-10 flex-1 resize-none"
                />
                <Button
                    type="button"
                    size="icon"
                    aria-label="Send message"
                    disabled={!canSubmit}
                    onClick={() => void submit()}
                >
                    {sending ? <Spinner /> : <SendHorizonal />}
                </Button>
            </div>
        </div>
    );
}
