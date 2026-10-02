import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ContactInquiryController from '@/actions/App/Http/Controllers/Platform/ContactInquiryController';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Message,
    MessageAvatar,
    MessageContent,
    MessageFooter,
    MessageHeader,
} from '@/components/ui/message';
import { formatAppDateTime } from '@/lib/datetime';

type ContactInquiryDetail = {
    id: number;
    full_name: string;
    phone: string;
    email: string | null;
    type: string;
    type_label: string;
    message: string;
    status: string;
    status_label: string;
    read_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
};

function Fact({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </dt>
            <dd className="text-sm font-medium wrap-break-word">{value}</dd>
        </div>
    );
}

function displayValue(value: string | null | undefined): string {
    return value && value !== '' ? value : '—';
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function PlatformContactInquiriesShow({
    inquiry,
}: {
    inquiry: ContactInquiryDetail;
}) {
    return (
        <>
            <Head title={`Contact inquiry #${inquiry.id}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={`Inquiry #${inquiry.id}`}
                            description={inquiry.full_name}
                        />
                        <div className="flex flex-wrap gap-2">
                            <Badge
                                variant={
                                    inquiry.status === 'new'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {inquiry.status_label}
                            </Badge>
                            <Badge variant="outline">{inquiry.type_label}</Badge>
                        </div>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={ContactInquiryController.index.url()}>
                            Back
                        </Link>
                    </Button>
                </div>

                <Card className="shadow-sm">
                    <CardContent className="grid gap-4 p-4 sm:grid-cols-3">
                        <Fact
                            label="Submitted"
                            value={
                                formatAppDateTime(inquiry.created_at) || '—'
                            }
                        />
                        <Fact
                            label="Read at"
                            value={formatAppDateTime(inquiry.read_at) || '—'}
                        />
                        <Fact
                            label="IP address"
                            value={displayValue(inquiry.ip_address)}
                        />
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Contact</CardTitle>
                        <CardDescription>
                            Details submitted with this contact inquiry.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            <Fact label="Full name" value={inquiry.full_name} />
                            <Fact label="Phone" value={inquiry.phone} />
                            <Fact
                                label="Email"
                                value={displayValue(inquiry.email)}
                            />
                            <Fact label="Type" value={inquiry.type_label} />
                        </dl>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Message</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="bg-primary/5 ring-primary/15 rounded-xl p-4 ring-1">
                            <Message align="start">
                                <MessageContent>
                                    <Bubble
                                        variant="tinted"
                                        className="max-w-full"
                                    >
                                        <BubbleContent className="max-w-prose sm:max-w-2xl">
                                            <p className="wrap-break-word whitespace-pre-wrap">
                                                {displayValue(inquiry.message)}
                                            </p>
                                        </BubbleContent>
                                    </Bubble>
                                </MessageContent>
                            </Message>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PlatformContactInquiriesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Contact inquiries',
            href: '/platform/contact-inquiries',
        },
        { title: 'Detail', href: '#' },
    ],
};
