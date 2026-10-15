import { Head, Link, router, useForm } from '@inertiajs/react';
import { MapPin, ShoppingBag, XCircle } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import CustomerController from '@/actions/App/Http/Controllers/Platform/CustomerController';
import OrderController from '@/actions/App/Http/Controllers/Platform/OrderController';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Platform/WholesaleInquiryController';
import {
    BranchLocationMapDialog,
    type BranchMapLinks,
} from '@/components/admin/branch-location-map-dialog';
import { RejectReasonDialog } from '@/components/admin/reject-reason-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { WholesaleInquiryStatusBadge } from '@/components/orders/wholesale-inquiry-status-badge';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Message, MessageContent } from '@/components/ui/message';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatAppDateTime } from '@/lib/datetime';

type WholesaleInquiryItem = {
    id: number;
    product_id: number;
    variant_id: number | null;
    name: string;
    sku: string | null;
    quantity: number;
};

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
    message: string | null;
    status: string;
    status_label: string;
    is_open: boolean;
    read_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
    admin_note: string | null;
    rejection_reason: string | null;
    reviewed_at: string | null;
    reviewer: string | null;
    customer: { id: number; name: string; phone: string | null } | null;
    order: { id: number; order_number: string } | null;
    items: WholesaleInquiryItem[];
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

export default function PlatformWholesaleInquiriesShow({
    inquiry,
    canViewOrders,
    canViewCustomers,
}: {
    inquiry: WholesaleInquiryDetail;
    canViewOrders: boolean;
    canViewCustomers: boolean;
}) {
    const [mapOpen, setMapOpen] = useState(false);
    const [rejectOpen, setRejectOpen] = useState(false);
    const [rejecting, setRejecting] = useState(false);
    const [rejectError, setRejectError] = useState<string | undefined>();
    const noteForm = useForm({ admin_note: inquiry.admin_note ?? '' });
    const latitude = formatCoord(inquiry.latitude);
    const longitude = formatCoord(inquiry.longitude);
    const hasPin =
        inquiry.map !== null && latitude !== null && longitude !== null;
    const totalQuantity = inquiry.items.reduce(
        (sum, item) => sum + item.quantity,
        0,
    );

    const reject = (reason: string): void => {
        router.post(
            WholesaleInquiryController.reject.url(inquiry.id),
            { reason },
            {
                preserveScroll: true,
                onStart: () => {
                    setRejecting(true);
                    setRejectError(undefined);
                },
                onFinish: () => setRejecting(false),
                onSuccess: () => setRejectOpen(false),
                onError: (errors) => setRejectError(errors.reason),
            },
        );
    };

    const saveNote = (event: FormEvent): void => {
        event.preventDefault();
        noteForm.patch(WholesaleInquiryController.updateNote.url(inquiry.id), {
            preserveScroll: true,
            onSuccess: () => noteForm.setDefaults(),
        });
    };

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
                        <WholesaleInquiryStatusBadge
                            status={inquiry.status}
                            label={inquiry.status_label}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={WholesaleInquiryController.index.url()}>
                                Back
                            </Link>
                        </Button>
                        {inquiry.is_open ? (
                            <>
                                <RejectReasonDialog
                                    title={`Reject inquiry #${inquiry.id}`}
                                    description="The reason is saved on the inquiry and shared with the customer when they have an account."
                                    reasonPlaceholder="e.g. Minimum order not met for these products"
                                    open={rejectOpen}
                                    onOpenChange={(next) => {
                                        setRejectOpen(next);

                                        if (!next) {
                                            setRejectError(undefined);
                                        }
                                    }}
                                    onConfirm={reject}
                                    processing={rejecting}
                                    error={rejectError}
                                >
                                    <Button variant="outline">
                                        <XCircle className="size-4" />
                                        Reject
                                    </Button>
                                </RejectReasonDialog>
                                <Button asChild>
                                    <Link
                                        href={WholesaleInquiryController.placeOrder.url(
                                            inquiry.id,
                                        )}
                                    >
                                        <ShoppingBag className="size-4" />
                                        Place order
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <Button disabled>
                                <ShoppingBag className="size-4" />
                                Place order
                            </Button>
                        )}
                    </div>
                </div>

                {!inquiry.is_open ? (
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Review</CardTitle>
                            <CardDescription>
                                {inquiry.status === 'converted'
                                    ? 'This inquiry was converted into an order.'
                                    : 'This inquiry was rejected.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-3">
                                <Fact
                                    label="Reviewed by"
                                    value={displayValue(inquiry.reviewer)}
                                />
                                <Fact
                                    label="Reviewed at"
                                    value={
                                        formatAppDateTime(
                                            inquiry.reviewed_at,
                                        ) || '—'
                                    }
                                />
                                {inquiry.order ? (
                                    <Fact
                                        label="Order"
                                        value={
                                            canViewOrders ? (
                                                <Link
                                                    href={OrderController.show.url(
                                                        inquiry.order.id,
                                                    )}
                                                    className="text-primary hover:underline"
                                                >
                                                    {inquiry.order.order_number}
                                                </Link>
                                            ) : (
                                                inquiry.order.order_number
                                            )
                                        }
                                    />
                                ) : null}
                                {inquiry.rejection_reason ? (
                                    <div className="sm:col-span-3">
                                        <Fact
                                            label="Rejection reason"
                                            value={
                                                <span className="whitespace-pre-wrap">
                                                    {inquiry.rejection_reason}
                                                </span>
                                            }
                                        />
                                    </div>
                                ) : null}
                            </dl>
                        </CardContent>
                    </Card>
                ) : null}

                <Card className="shadow-sm">
                    <CardContent className="grid gap-4 p-4 sm:grid-cols-3">
                        <Fact
                            label="Submitted"
                            value={formatAppDateTime(inquiry.created_at) || '—'}
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
                                <Fact
                                    label="Customer account"
                                    value={
                                        inquiry.customer === null ? (
                                            'Guest'
                                        ) : canViewCustomers ? (
                                            <Link
                                                href={CustomerController.show.url(
                                                    inquiry.customer.id,
                                                )}
                                                className="text-primary hover:underline"
                                            >
                                                {inquiry.customer.name}
                                            </Link>
                                        ) : (
                                            inquiry.customer.name
                                        )
                                    }
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
                        <CardTitle>Requested products</CardTitle>
                        <CardDescription>
                            {inquiry.items.length === 0
                                ? 'No products were picked — see the message below.'
                                : `${inquiry.items.length} ${inquiry.items.length === 1 ? 'product' : 'products'} · ${totalQuantity} units`}
                        </CardDescription>
                    </CardHeader>
                    {inquiry.items.length > 0 ? (
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Product</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead className="text-right">
                                            Quantity
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {inquiry.items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium">
                                                {item.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {displayValue(item.sku)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {item.quantity}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    ) : null}
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

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Internal note</CardTitle>
                        <CardDescription>
                            Visible to platform staff only.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={saveNote} className="grid gap-3">
                            <Textarea
                                rows={3}
                                value={noteForm.data.admin_note}
                                onChange={(event) =>
                                    noteForm.setData(
                                        'admin_note',
                                        event.target.value,
                                    )
                                }
                                placeholder="Follow-up notes, agreed terms, …"
                            />
                            <InputError message={noteForm.errors.admin_note} />
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={
                                        noteForm.processing || !noteForm.isDirty
                                    }
                                >
                                    Save note
                                </Button>
                            </div>
                        </form>
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
