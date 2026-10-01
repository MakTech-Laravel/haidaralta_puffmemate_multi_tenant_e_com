import { Head, Link } from '@inertiajs/react';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Platform/WholesaleInquiryController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatAppDateTime } from '@/lib/datetime';

type WholesaleInquiryDetail = {
    id: number;
    full_name: string;
    phone: string;
    company: string | null;
    ask_question: string | null;
    email: string | null;
    address: string;
    message: string;
    status: string;
    status_label: string;
    read_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
};

function Field({
    label,
    value,
}: {
    label: string;
    value: string | null | undefined;
}) {
    return (
        <div>
            <p className="text-muted-foreground text-xs uppercase">{label}</p>
            <p className="text-foreground mt-1 whitespace-pre-wrap">
                {value && value !== '' ? value : '—'}
            </p>
        </div>
    );
}

export default function PlatformWholesaleInquiriesShow({
    inquiry,
}: {
    inquiry: WholesaleInquiryDetail;
}) {
    return (
        <>
            <Head title={`Wholesale inquiry #${inquiry.id}`} />

            <div className="space-y-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`Wholesale inquiry #${inquiry.id}`}
                        description={inquiry.full_name}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" asChild>
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

                <div className="border-border bg-card grid gap-4 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="text-muted-foreground text-xs uppercase">
                            Status
                        </p>
                        <div className="mt-1">
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
                    </div>
                    <Field
                        label="Submitted"
                        value={formatAppDateTime(inquiry.created_at) || null}
                    />
                    <Field
                        label="Read at"
                        value={formatAppDateTime(inquiry.read_at) || null}
                    />
                    <Field label="IP address" value={inquiry.ip_address} />
                </div>

                <div className="border-border bg-card grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <Field label="Full name" value={inquiry.full_name} />
                    <Field label="Phone" value={inquiry.phone} />
                    <Field label="Email" value={inquiry.email} />
                    <Field label="Company" value={inquiry.company} />
                    <Field label="Ask question" value={inquiry.ask_question} />
                    <Field label="Address" value={inquiry.address} />
                    <div className="sm:col-span-2">
                        <Field label="Message" value={inquiry.message} />
                    </div>
                </div>
            </div>
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
