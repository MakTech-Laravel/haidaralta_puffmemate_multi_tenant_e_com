import { Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    Mail,
    MapPin,
    Phone,
    UserRound,
    X as XIcon,
} from 'lucide-react';
import type { ReactNode } from 'react';
import CustomerController from '@/actions/App/Http/Controllers/Branch/CustomerController';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { formatAppDateTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type Branch = { id: number; name: string; slug: string };

type Customer = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    avatar_url: string | null;
    is_active: boolean;
    registration_status: string | null;
    registration_status_label: string;
    phone_verified_at: string | null;
    email_verified_at: string | null;
    address: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type RecentOrder = {
    id: number;
    fulfillmentId: number | null;
    order_number: string;
    status: string;
    status_label: string;
    payment_status_label: string;
    total_label: string;
    created_at: string | null;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return formatAppDateTime(value) || '—';
}

function FactRow({
    icon,
    label,
    value,
}: {
    icon: ReactNode;
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex items-start gap-3">
            <div className="text-muted-foreground mt-0.5 shrink-0">{icon}</div>
            <div className="min-w-0 flex-1">
                <p className="text-muted-foreground text-xs">{label}</p>
                <p className="text-sm font-medium wrap-break-word">{value}</p>
            </div>
        </div>
    );
}

function VerifiedMark({ ok }: { ok: boolean }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs font-medium',
                ok
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-muted-foreground',
            )}
        >
            {ok ? (
                <Check className="size-3.5" />
            ) : (
                <XIcon className="size-3.5" />
            )}
            {ok ? 'Verified' : 'Not verified'}
        </span>
    );
}

function OrderRowContent({
    order,
    showChevron,
}: {
    order: RecentOrder;
    showChevron: boolean;
}) {
    return (
        <>
            <div className="min-w-0 flex-1 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium">
                        {order.order_number}
                    </span>
                    <Badge variant="secondary">{order.status_label}</Badge>
                </div>
                <p className="text-muted-foreground text-xs">
                    {order.payment_status_label} ·{' '}
                    {formatDateTime(order.created_at)}
                </p>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <span className="text-sm font-medium tabular-nums">
                    {order.total_label}
                </span>
                {showChevron ? (
                    <ChevronRight className="text-muted-foreground size-4" />
                ) : null}
            </div>
        </>
    );
}

export default function BranchCustomersShow({
    branch,
    customer,
    recentOrders,
}: {
    branch: Branch;
    customer: Customer;
    recentOrders: RecentOrder[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Customers',
                href: CustomerController.index.url(branch.slug),
            },
            {
                title: customer.name,
                href: '#',
            },
        ],
    });

    return (
        <>
            <Head title={customer.name} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link
                            href={CustomerController.index.url(branch.slug)}
                        >
                            All customers
                        </Link>
                    </Button>
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[19rem_minmax(0,1fr)]">
                    <aside className="bg-card rounded-2xl border">
                        <div className="flex flex-col items-center gap-3 px-6 pt-8 pb-6 text-center">
                            <Avatar className="size-24 border shadow-sm">
                                {customer.avatar_url ? (
                                    <AvatarImage
                                        src={customer.avatar_url}
                                        alt={customer.name}
                                        className="object-cover"
                                    />
                                ) : null}
                                <AvatarFallback className="text-xl font-semibold">
                                    {initials(customer.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="space-y-2">
                                <h1 className="text-xl font-semibold tracking-tight">
                                    {customer.name}
                                </h1>
                                <Badge
                                    variant={
                                        customer.is_active
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {customer.is_active
                                        ? 'Active'
                                        : 'Inactive'}
                                </Badge>
                            </div>
                        </div>
                        <Separator />
                        <div className="flex flex-col gap-4 px-6 py-5">
                            <FactRow
                                icon={<Phone className="size-4" />}
                                label="Phone"
                                value={
                                    <span className="flex flex-col gap-1">
                                        <span>{customer.phone}</span>
                                        <VerifiedMark
                                            ok={
                                                customer.phone_verified_at !==
                                                null
                                            }
                                        />
                                    </span>
                                }
                            />
                            <FactRow
                                icon={<Mail className="size-4" />}
                                label="Email"
                                value={
                                    <span className="flex flex-col gap-1">
                                        <span>{customer.email ?? '—'}</span>
                                        <VerifiedMark
                                            ok={
                                                customer.email_verified_at !==
                                                null
                                            }
                                        />
                                    </span>
                                }
                            />
                            <FactRow
                                icon={<UserRound className="size-4" />}
                                label="Registration"
                                value={customer.registration_status_label}
                            />
                            <FactRow
                                icon={<MapPin className="size-4" />}
                                label="Shipping address"
                                value={customer.address ?? '—'}
                            />
                        </div>
                        <Separator />
                        <dl className="grid grid-cols-2 gap-4 px-6 py-5">
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Created
                                </dt>
                                <dd className="text-sm font-medium">
                                    {formatDateTime(customer.created_at)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Updated
                                </dt>
                                <dd className="text-sm font-medium">
                                    {formatDateTime(customer.updated_at)}
                                </dd>
                            </div>
                        </dl>
                    </aside>

                    <section className="bg-card overflow-hidden rounded-2xl border">
                        <div className="px-5 py-4">
                            <h2 className="text-base font-semibold tracking-tight">
                                Recent orders
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Latest orders fulfilled by this branch
                            </p>
                        </div>
                        <Separator />
                        {recentOrders.length === 0 ? (
                            <p className="text-muted-foreground px-5 py-10 text-center text-sm">
                                No orders at this branch yet.
                            </p>
                        ) : (
                            <ul>
                                {recentOrders.map((order) => (
                                    <li
                                        key={order.id}
                                        className="border-b last:border-b-0"
                                    >
                                        {order.fulfillmentId ? (
                                            <Link
                                                href={OrderController.show.url([
                                                    branch.slug,
                                                    order.fulfillmentId,
                                                ])}
                                                className="hover:bg-muted/40 flex items-center gap-3 px-5 py-3.5 transition-colors"
                                            >
                                                <OrderRowContent
                                                    order={order}
                                                    showChevron
                                                />
                                            </Link>
                                        ) : (
                                            <div className="flex items-center gap-3 px-5 py-3.5">
                                                <OrderRowContent
                                                    order={order}
                                                    showChevron={false}
                                                />
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
