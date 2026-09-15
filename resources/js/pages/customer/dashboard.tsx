import { Head, Link, usePage } from '@inertiajs/react';
import { FileCheck2, Link2, Star, UserRound } from 'lucide-react';
import type { ComponentType } from 'react';
import { cn } from '@/lib/utils';
import { logout, wholesale } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import type { Auth } from '@/types';

type AccountCard = {
    label: string;
    href: string;
    icon: ComponentType<{ className?: string; strokeWidth?: number }>;
};

const cards: AccountCard[] = [
    {
        label: 'Orders',
        href: '/activity',
        icon: FileCheck2,
    },
    {
        label: 'Product To Review',
        href: '/dashboard',
        icon: Star,
    },
    {
        label: 'Account',
        href: editProfile.url(),
        icon: UserRound,
    },
    {
        label: 'Wholesale',
        href: wholesale.url(),
        icon: Link2,
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
                    <p className="text-foreground text-base sm:text-lg">
                        Hello{' '}
                        <span className="font-semibold">{firstName}</span>{' '}
                        (Not {firstName}?{' '}
                        <Link
                            href={logout()}
                            as="button"
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Log Out
                        </Link>
                        )
                    </p>
                    <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed sm:text-base">
                        From Your Account Dashboard, You Can Easily Check Your{' '}
                        <Link
                            href="/activity"
                            prefetch
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Recent Orders
                        </Link>
                        , Manage Your{' '}
                        <Link
                            href={editProfile.url()}
                            prefetch
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Shipping And Billing Addresses
                        </Link>
                        , And Track Your{' '}
                        <Link
                            href="/activity"
                            prefetch
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Orders
                        </Link>
                        .
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                    {cards.map((card) => (
                        <Link
                            key={card.label}
                            href={card.href}
                            prefetch
                            className={cn(
                                'border-border bg-background hover:border-primary/50 flex min-h-40 flex-col items-center justify-center gap-4 border px-6 py-10 transition',
                            )}
                        >
                            <card.icon
                                className="text-primary size-14"
                                strokeWidth={1.5}
                                aria-hidden
                            />
                            <span className="text-foreground text-center text-lg font-medium">
                                {card.label}
                            </span>
                        </Link>
                    ))}
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
