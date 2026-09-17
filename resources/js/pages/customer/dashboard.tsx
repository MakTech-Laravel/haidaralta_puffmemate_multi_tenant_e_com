import { Head, Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { logout, wholesale } from '@/routes';
import { index as accountIndex } from '@/routes/customer/account';
import { index as productToReview } from '@/routes/customer/product-to-review';
import { edit as editProfile } from '@/routes/profile';
import type { Auth } from '@/types';

type AccountCard = {
    label: string;
    href: string;
    image: string;
};

const cards: AccountCard[] = [
    {
        label: 'Orders',
        href: '/orders',
        image: '/images/customer/Vector.svg',
    },
    {
        label: 'Product To Review',
        href: productToReview.url(),
        image: '/images/customer/Vector (1).svg',
    },
    {
        label: 'Account',
        href: accountIndex.url(),
        image: '/images/customer/Vector (2).svg',
    },
    {
        label: 'Wholesale',
        href: wholesale.url(),
        image: '/images/customer/Frame.svg',
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
                    <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                        From Your Account Dashboard, You Can Easily Check Your{' '}
                        <Link
                            href="/orders"
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
                            href="/orders"
                            prefetch
                            className="text-primary font-medium underline underline-offset-2"
                        >
                            Orders
                        </Link>
                        .
                    </p>
                </div>

                <div className="grid gap-4 grid-cols-2">
                    {cards.map((card) => (
                        <Link
                            key={card.label}
                            href={card.href}
                            prefetch
                            className={cn(
                                'border-border bg-background hover:border-primary/50 flex min-h-28 flex-col items-center justify-center gap-2 border px-3 py-5 transition sm:min-h-40 sm:gap-4 sm:px-6 sm:py-10',
                            )}
                        >
                            <img
                                src={card.image}
                                alt={card.label}
                                className="size-12 sm:size-20"
                            />
                            <span className="text-foreground text-center text-base font-medium">
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
