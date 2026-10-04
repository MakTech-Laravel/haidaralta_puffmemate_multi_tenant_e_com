import { Head, Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Platform/WholesaleInquiryController';
import {
    BranchLocationMapDialog,
    type BranchMapLinks,
} from '@/components/admin/branch-location-map-dialog';
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

type WholesaleInquiryDetail = {
    id: number;
    full_name: string;
    phone: string;
    company: string | null;
    email: string | null;
    address: string;
    latitude: string | null;
    longitude: string | null;
    map: BranchMapLinks | null;
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

function formatCoord(value: string | null): string | null {
    if (value === null || value === '') {
        return null;
    }

    const numeric = Number(value);

    if (Number.isFinite(numeric)) {
        return numeric.toFixed(7);
    }

    return value;
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

export default function PlatformWholesaleInquiriesShow({
    inquiry,
}: {
    inquiry: WholesaleInquiryDetail;
}) {
    const [mapOpen, setMapOpen] = useState(false);
    const latitude = formatCoord(inquiry.latitude);
    const longitude = formatCoord(inquiry.longitude);
    const hasPin =
        inquiry.map !== null && latitude !== null && longitude !== null;

    return (
        <>
            <Head title={`Wholesale inquiry #${inquiry.id}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={`Inquiry #${inquiry.id}`}
                            description={inquiry.full_name}
                        />
                        <Badge
                            variant={
                                inquiry.status === 'new'
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {inquiry.status_label}
                        </Badge>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={WholesaleInquiryController.index.url()}>
                                Back
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={WholesaleInquiryController.placeOrder.url(
                                    inquiry.id,
                                )}
                            >
                                Place order
                            </Link>
                        </Button>
                    </div>
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

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Contact</CardTitle>
                            <CardDescription>
                                Details submitted with this wholesale inquiry.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                <Fact
                                    label="Full name"
                                    value={inquiry.full_name}
                                />
                                <Fact label="Phone" value={inquiry.phone} />
                                <Fact
                                    label="Email"
                                    value={displayValue(inquiry.email)}
                                />
                                <Fact
                                    label="Company"
                                    value={displayValue(inquiry.company)}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-2 space-y-0">
                            <div className="space-y-1.5">
                                <CardTitle>Location</CardTitle>
                                <CardDescription>
                                    Address and map pin from the storefront
                                    form.
                                </CardDescription>
                            </div>
                            {hasPin ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setMapOpen(true)}
                                >
                                    <MapPin className="size-4" />
                                    View on map
                                </Button>
                            ) : null}
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Fact
                                        label="Address"
                                        value={inquiry.address}
                                    />
                                </div>
                                <Fact
                                    label="Latitude"
                                    value={
                                        <span className="tabular-nums">
                                            {latitude ?? '—'}
                                        </span>
                                    }
                                />
                                <Fact
                                    label="Longitude"
                                    value={
                                        <span className="tabular-nums">
                                            {longitude ?? '—'}
                                        </span>
                                    }
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </div>

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
                                        <BubbleContent className="max-w-prose sm:max-w-full">
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

            {hasPin && inquiry.map && latitude && longitude ? (
                <BranchLocationMapDialog
                    open={mapOpen}
                    onOpenChange={setMapOpen}
                    links={inquiry.map}
                    latitude={latitude}
                    longitude={longitude}
                    name={inquiry.full_name}
                    address={inquiry.address}
                />
            ) : null}
        </>
    );
}

PlatformWholesaleInquiriesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Wholesale inquiries',
            href: '/platform/wholesale-inquiries',
        },
        { title: 'Detail', href: '#' },
    ],
};
