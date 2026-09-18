import { router } from '@inertiajs/react';
import { Check, MessageSquarePlus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { fetchOrderOptions } from '@/lib/conversation';
import type { OrderOption } from './types';

/**
 * Opens a thread against an order that does not have one yet.
 *
 * The picker queries the server rather than embedding the order catalog, the
 * same rule the stock and price-history filters follow.
 */
export function NewConversationDialog({
    optionsUrl,
    storeUrl,
}: {
    optionsUrl: string;
    storeUrl: string;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<OrderOption[]>([]);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState<OrderOption | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            setLoading(true);

            fetchOrderOptions(optionsUrl, search, controller.signal)
                .then(setOptions)
                .catch(() => undefined)
                .finally(() => setLoading(false));
        }, 250);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [open, search, optionsUrl]);

    const submit = () => {
        if (!selected) {
            return;
        }

        setSubmitting(true);

        router.post(
            storeUrl,
            { order_id: selected.value },
            {
                onError: () => toast.error('Could not open the conversation.'),
                onFinish: () => {
                    setSubmitting(false);
                    setOpen(false);
                    setSelected(null);
                    setSearch('');
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label="New conversation"
                >
                    <MessageSquarePlus />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Open an order conversation</DialogTitle>
                    <DialogDescription>
                        Pick an order that does not have a conversation yet. The
                        customer and assigned driver join automatically.
                    </DialogDescription>
                </DialogHeader>

                <Command shouldFilter={false}>
                    <CommandInput
                        value={search}
                        onValueChange={setSearch}
                        placeholder="Search by order number…"
                    />
                    <CommandList>
                        {loading ? (
                            <div className="text-muted-foreground flex items-center justify-center gap-2 py-6 text-sm">
                                <Spinner />
                                Loading orders…
                            </div>
                        ) : (
                            <CommandEmpty>
                                No orders are waiting for a conversation.
                            </CommandEmpty>
                        )}
                        {options.map((option) => (
                            <CommandItem
                                key={option.value}
                                value={String(option.value)}
                                onSelect={() => setSelected(option)}
                            >
                                <div className="flex min-w-0 flex-1 flex-col">
                                    <span className="truncate font-medium">
                                        {option.label}
                                    </span>
                                    <span className="text-muted-foreground truncate text-xs">
                                        {option.description}
                                    </span>
                                </div>
                                {selected?.value === option.value ? (
                                    <Check className="size-4" />
                                ) : null}
                            </CommandItem>
                        ))}
                    </CommandList>
                </Command>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => setOpen(false)}
                        disabled={submitting}
                    >
                        Cancel
                    </Button>
                    <Button onClick={submit} disabled={!selected || submitting}>
                        {submitting ? <Spinner /> : null}
                        Open conversation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
