import { Head, Link, usePage } from '@inertiajs/react';
import {
    Bell,
    ClipboardCheck,
    Star,
    User,
    type LucideIcon,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { index as accountIndex } from '@/routes/customer/account';
import { index as notificationsIndex } from '@/routes/customer/notifications';
import { index as productToReview } from '@/routes/customer/product-to-review';
import type { Auth } from '@/types';

type AccountCard = {
    label: string;
    href: string;
    icon: LucideIcon;
};

const cards: AccountCard[] = [
    {
        label: 'Orders',
        href: '/orders',
        icon: ClipboardCheck,
    },
    {
        label: 'Product To Review',
        href: productToReview.url(),
        icon: Star,
    },
    {
        label: 'Notification',
        href: notificationsIndex.url(),
        icon: Bell,
    },
    {
        label: 'Account',
        href: accountIndex.url(),
        icon: User,
    },
];

export default function CustomerDashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const firstName = auth.user?.name.split(/\s+/u)[0] ?? 'there';

    return (
        <>
            <Head title="My Account" />

            <div className="flex flex-col gap-8">
                <div className="space-y-4">
                    <p className="text-foreground text-base">
                        Hello <span className="font-semibold">{firstName}</span>
                        <span className="hidden sm:inline">
                            {' '}
                            (Not {firstName}?{' '}
                            <Link
                                href={logout()}
                                as="button"
                                className="text-primary font-medium underline underline-offset-2"
                            >
                                Log Out
                            </Link>
                            )
                        </span>
                    </p>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                        From Your Account Dashboard, You Can Easily Check Your{' '}
                        <span className="text-primary font-medium">
                            Recent Orders
                        </span>
                        , Manage Your{' '}
                        <span className="text-primary font-medium">
                            Shipping And Billing Addresses
                        </span>
                        , And Track Your{' '}
                        <span className="text-primary font-medium">Orders</span>
                        .
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    {cards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <Link
                                key={card.label}
                                href={card.href}
                                prefetch
                                className={cn(
                                    'border-border bg-background hover:border-primary flex min-h-28 flex-col items-center justify-center gap-2 border px-3 py-5 transition sm:min-h-40 sm:gap-4 sm:px-6 sm:py-10',
                                )}
                            >
                                <Icon
                                    className="text-primary size-12 sm:size-20"
                                    strokeWidth={1.25}
                                    aria-hidden
                                />
                                <span className="text-muted-foreground text-center text-base font-medium">
                                    {card.label}
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

CustomerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ],
};
