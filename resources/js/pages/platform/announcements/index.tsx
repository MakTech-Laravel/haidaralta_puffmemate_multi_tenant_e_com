import { Head, useForm } from '@inertiajs/react';
import {
    Bell,
    Check,
    ChevronsUpDown,
    Lock,
    Mail,
    Megaphone,
    Phone,
    Shield,
    Store,
    Truck,
    UserRound,
    Users,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import {
    recipients as searchRecipients,
    store,
} from '@/actions/App/Http/Controllers/Platform/AnnouncementController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { LucideIconPicker } from '@/components/icons/lucide-icon-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

const sectionCardClass = 'border-border/80 bg-card rounded-2xl border p-5 shadow-sm';

type SendMode = 'group' | 'individual';
type AudienceId = 'customer' | 'driver' | 'branch' | 'platform';
type MessageType = 'info' | 'promo' | 'alert' | 'update';
type ChannelId = 'phone' | 'email' | 'push' | 'system';

type Person = {
    id: number;
    name: string;
    phone: string;
    audience: AudienceId;
};

const AUDIENCES: {
    id: AudienceId;
    label: string;
    placeholder: string;
    cardTitle: string;
    cardDescription: string;
    icon: LucideIcon;
}[] = [
    {
        id: 'customer',
        label: 'Customers',
        placeholder: 'Select customers',
        cardTitle: 'Customer',
        cardDescription: 'Storefront shoppers',
        icon: Users,
    },
    {
        id: 'driver',
        label: 'Drivers',
        placeholder: 'Select drivers',
        cardTitle: 'Driver',
        cardDescription: 'Delivery drivers',
        icon: Truck,
    },
    {
        id: 'branch',
        label: 'Branch',
        placeholder: 'Select staff',
        cardTitle: 'Branch',
        cardDescription: 'Branch staff',
        icon: Store,
    },
    {
        id: 'platform',
        label: 'Platform',
        placeholder: 'Select platform',
        cardTitle: 'Platform',
        cardDescription: 'Platform staff',
        icon: Shield,
    },
];

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function fetchPeople(
    audience: AudienceId,
    query: string,
    signal?: AbortSignal,
): Promise<Person[]> {
    const url = new URL(
        searchRecipients.url({ query: { audience, q: query } }),
        window.location.origin,
    );

    const response = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        return [];
    }

    const payload: { people?: Array<{ id: number; name: string; phone: string }> } =
        await response.json();

    return (payload.people ?? []).map((person) => ({
        ...person,
        audience,
    }));
}

const CHANNELS: {
    id: ChannelId;
    title: string;
    description: string;
    icon: LucideIcon;
    locked?: boolean;
}[] = [
    {
        id: 'phone',
        title: 'Phone',
        description: 'SMS notice',
        icon: Phone,
    },
    {
        id: 'email',
        title: 'Email',
        description: 'Inbox message',
        icon: Mail,
    },
    {
        id: 'push',
        title: 'Push',
        description: 'Device alert',
        icon: Bell,
    },
    {
        id: 'system',
        title: 'System',
        description: 'Always on',
        icon: Megaphone,
        locked: true,
    },
];

function ChoiceCard({
    title,
    description,
    icon: Icon,
    selected,
    disabled,
    locked,
    onClick,
}: {
    title: string;
    description: string;
    icon: LucideIcon;
    selected: boolean;
    disabled?: boolean;
    locked?: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'relative flex flex-col gap-1 rounded-xl border p-4 text-left transition-colors',
                selected
                    ? 'border-primary ring-primary/30 bg-primary/5 ring-2'
                    : 'border-border/80 hover:bg-accent/40',
                disabled && 'cursor-not-allowed opacity-80',
            )}
        >
            <div className="flex items-start justify-between gap-2">
                <Icon className="text-muted-foreground size-5" />
                {selected ? (
                    <span className="bg-primary text-primary-foreground flex size-5 items-center justify-center rounded-full">
                        {locked ? (
                            <Lock className="size-3" />
                        ) : (
                            <Check className="size-3" />
                        )}
                    </span>
                ) : null}
            </div>
            <p className="text-foreground mt-2 text-sm font-medium">{title}</p>
            <p className="text-muted-foreground text-xs">{description}</p>
        </button>
    );
}

function AudienceCombobox({
    audience,
    selected,
    onToggle,
}: {
    audience: (typeof AUDIENCES)[number];
    selected: Person[];
    onToggle: (person: Person) => void;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [people, setPeople] = useState<Person[]>([]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            void fetchPeople(audience.id, query, controller.signal).then(
                setPeople,
            );
        }, 250);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [audience.id, open, query]);

    return (
        <div className="flex min-w-0 flex-col gap-2">
            <Label htmlFor={`who-${audience.id}`}>{audience.label}</Label>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={`who-${audience.id}`}
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="h-auto min-h-9 w-full min-w-0 justify-between gap-2 px-3 py-1.5 font-normal"
                    >
                        <span className="text-muted-foreground truncate">
                            {audience.placeholder}
                        </span>
                        <ChevronsUpDown className="size-4 shrink-0 self-center opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-(--radix-popover-trigger-width) p-0"
                    align="start"
                >
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder={`Search ${audience.label.toLowerCase()}…`}
                            value={query}
                            onValueChange={setQuery}
                        />
                        <CommandList>
                            <CommandEmpty>No people found.</CommandEmpty>
                            <CommandGroup>
                                {people.map((person) => {
                                    const isSelected = selected.some(
                                        (item) => item.id === person.id,
                                    );

                                    return (
                                        <CommandItem
                                            key={person.id}
                                            value={`${person.name} ${person.phone}`}
                                            onSelect={() => onToggle(person)}
                                        >
                                            <Check
                                                className={cn(
                                                    'size-4',
                                                    isSelected
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                            <span className="flex min-w-0 flex-1 items-baseline justify-between gap-3">
                                                <span className="truncate">
                                                    {person.name}
                                                </span>
                                                <span className="text-muted-foreground shrink-0 text-xs">
                                                    {person.phone}
                                                </span>
                                            </span>
                                        </CommandItem>
                                    );
                                })}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    );
}

export default function PlatformAnnouncementsIndex() {
    const [sendMode, setSendMode] = useState<SendMode>('group');
    const [audiences, setAudiences] = useState<AudienceId[]>([]);
    const [recipients, setRecipients] = useState<Person[]>([]);
    const [messageType, setMessageType] = useState<MessageType>('info');
    const [iconId, setIconId] = useState('bell');
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [showLink, setShowLink] = useState(false);
    const [linkLabel, setLinkLabel] = useState('');
    const [linkUrl, setLinkUrl] = useState('');
    const [channels, setChannels] = useState<ChannelId[]>(['system']);
    const form = useForm({
        send_mode: 'group',
        audiences: [] as AudienceId[],
        recipients: [] as Array<{ id: number; audience: AudienceId }>,
        type: 'info',
        icon: 'bell',
        title: '',
        message: '',
        link_label: '',
        link_url: '',
        via_phone: false,
        via_email: false,
        via_push: false,
    });

    const toggleAudience = (id: AudienceId) => {
        setAudiences((current) =>
            current.includes(id)
                ? current.filter((item) => item !== id)
                : [...current, id],
        );
    };

    const toggleRecipient = (person: Person) => {
        setRecipients((current) =>
            current.some((item) => item.id === person.id)
                ? current.filter((item) => item.id !== person.id)
                : [...current, person],
        );
    };

    const toggleChannel = (id: ChannelId) => {
        if (id === 'system') {
            return;
        }

        setChannels((current) =>
            current.includes(id)
                ? current.filter((item) => item !== id)
                : [...current, id],
        );
    };

    const selectedByAudience = useMemo(
        () =>
            AUDIENCES.map((audience) => ({
                ...audience,
                people: recipients.filter(
                    (person) => person.audience === audience.id,
                ),
            })),
        [recipients],
    );

    const submit = () => {
        form.transform(() => ({
            send_mode: sendMode,
            audiences,
            recipients: recipients.map((person) => ({
                id: person.id,
                audience: person.audience,
            })),
            type: messageType,
            icon: iconId,
            title,
            message: description,
            link_label: showLink ? linkLabel : '',
            link_url: showLink ? linkUrl : '',
            via_phone: channels.includes('phone'),
            via_email: channels.includes('email'),
            via_push: channels.includes('push'),
        }));

        form.submit(store(), {
            preserveScroll: true,
            onSuccess: () => {
                setTitle('');
                setDescription('');
                setRecipients([]);
                setAudiences([]);
                setLinkLabel('');
                setLinkUrl('');
                setShowLink(false);
            },
        });
    };

    return (
        <>
            <Head title="Announcement" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Announcement"
                    description="Send a notice to a group or to chosen people"
                />

                <section className={sectionCardClass}>
                    <Heading
                        variant="small"
                        title="1. How to send"
                        description="Choose a whole audience or pick people one by one."
                    />
                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        <ChoiceCard
                            title="Group"
                            description="Whole audience"
                            icon={Users}
                            selected={sendMode === 'group'}
                            onClick={() => setSendMode('group')}
                        />
                        <ChoiceCard
                            title="Individual"
                            description="Pick people"
                            icon={UserRound}
                            selected={sendMode === 'individual'}
                            onClick={() => setSendMode('individual')}
                        />
                    </div>
                </section>

                <section className={sectionCardClass}>
                    <Heading
                        variant="small"
                        title="2. Who"
                        description="Any mix of Customer, Driver, Branch, and Platform."
                    />
                    <InputError
                        message={form.errors.audiences ?? form.errors.recipients}
                    />
                    {sendMode === 'group' ? (
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 md:grid-cols-4">
                            {AUDIENCES.map((audience) => (
                                <ChoiceCard
                                    key={audience.id}
                                    title={audience.cardTitle}
                                    description={audience.cardDescription}
                                    icon={audience.icon}
                                    selected={audiences.includes(audience.id)}
                                    onClick={() => toggleAudience(audience.id)}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 md:grid-cols-4">
                            {AUDIENCES.map((audience) => (
                                <AudienceCombobox
                                    key={audience.id}
                                    audience={audience}
                                    selected={recipients.filter(
                                        (person) =>
                                            person.audience === audience.id,
                                    )}
                                    onToggle={toggleRecipient}
                                />
                            ))}
                        </div>
                    )}
                </section>

                {sendMode === 'individual' ? (
                    <section className={sectionCardClass}>
                        <Heading
                            variant="small"
                            title="3. Recipients"
                            description="People picked in Who."
                        />

                        <div className="mt-4 space-y-8">
                            {selectedByAudience.map((group) => (
                                <div key={group.id} className="space-y-2 mb-4">
                                    <p className="text-sm font-medium">
                                        {group.label}
                                    </p>
                                    {group.people.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {group.people.map((person) => (
                                                <Badge
                                                    key={person.id}
                                                    variant="secondary"
                                                    className="gap-1 pr-1 font-normal"
                                                >
                                                    {person.name} · {person.phone}
                                                    <button
                                                        type="button"
                                                        className="hover:bg-background/60 rounded-sm p-0.5"
                                                        onClick={() =>
                                                            toggleRecipient(
                                                                person,
                                                            )
                                                        }
                                                        aria-label={`Remove ${person.name}`}
                                                    >
                                                        <X className="size-3" />
                                                    </button>
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                    </section>
                ) : null}

                <section className={sectionCardClass}>
                    <Heading
                        variant="small"
                        title={sendMode === 'individual' ? '4. Message' : '3. Message'}
                        description="Type, icon, title, description, and an optional link."
                    />

                    <div className="mt-4 flex flex-wrap items-end gap-3">
                        <div className="min-w-40 space-y-2">
                            <Label htmlFor="announcement-type">Type</Label>
                            <Select
                                value={messageType}
                                onValueChange={(value) =>
                                    setMessageType(value as MessageType)
                                }
                            >
                                <SelectTrigger
                                    id="announcement-type"
                                    className="w-full min-w-40"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="info">Info</SelectItem>
                                    <SelectItem value="promo">Promo</SelectItem>
                                    <SelectItem value="alert">Alert</SelectItem>
                                    <SelectItem value="update">
                                        Update
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Icon</Label>
                            <LucideIconPicker
                                mode="dialog"
                                triggerVariant="button"
                                value={iconId}
                                onChange={setIconId}
                                defaultIcon="bell"
                                fallbackIcon="bell"
                                recentsScope="announcement-compose"
                            />
                        </div>
                    </div>

                    <div className="mt-4 space-y-2">
                        <Label htmlFor="announcement-title">Title</Label>
                        <Input
                            id="announcement-title"
                            value={title}
                            onChange={(event) => setTitle(event.target.value)}
                            placeholder="Short headline"
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="mt-4 space-y-2">
                        <Label htmlFor="announcement-description">
                            Description
                        </Label>
                        <Textarea
                            id="announcement-description"
                            value={description}
                            onChange={(event) =>
                                setDescription(event.target.value)
                            }
                            placeholder="What should people know?"
                            rows={4}
                        />
                        <InputError message={form.errors.message} />
                    </div>

                    <div className="mt-4 space-y-3">
                        {/* <Label>Link</Label> */}
                        {showLink ? (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="announcement-link-label">
                                        Label
                                    </Label>
                                    <Input
                                        id="announcement-link-label"
                                        value={linkLabel}
                                        onChange={(event) =>
                                            setLinkLabel(event.target.value)
                                        }
                                        placeholder="Learn more"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="announcement-link-url">
                                        URL
                                    </Label>
                                    <Input
                                        id="announcement-link-url"
                                        value={linkUrl}
                                        onChange={(event) =>
                                            setLinkUrl(event.target.value)
                                        }
                                        placeholder="https://"
                                    />
                                </div>
                            </div>
                        ) : (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowLink(true)}
                            >
                                Add link
                            </Button>
                        )}
                    </div>
                </section>

                <section className={sectionCardClass}>
                    <Heading
                        variant="small"
                        title={sendMode === 'individual' ? '5. Send via' : '4. Send via'}
                        description="System stays on. Phone, Email, and Push are optional."
                    />
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {CHANNELS.map((channel) => (
                            <ChoiceCard
                                key={channel.id}
                                title={channel.title}
                                description={channel.description}
                                icon={channel.icon}
                                selected={channels.includes(channel.id)}
                                disabled={channel.locked}
                                locked={channel.locked}
                                onClick={() => toggleChannel(channel.id)}
                            />
                        ))}
                    </div>
                </section>

                <div className="flex justify-end">
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={form.processing}
                    >
                        Send announcement
                    </Button>
                </div>
            </div>
        </>
    );
}

PlatformAnnouncementsIndex.layout = {
    breadcrumbs: [
        { title: 'Announcement', href: '/platform/announcements' },
    ],
};
