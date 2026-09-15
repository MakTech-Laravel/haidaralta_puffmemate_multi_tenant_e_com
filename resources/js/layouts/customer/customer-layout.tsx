import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AccountSidebar } from '@/components/customer/account-sidebar';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';
import { home } from '@/routes';
import type { BreadcrumbItem } from '@/types';

export default function CustomerLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    const trail: BreadcrumbItem[] = [
        { title: 'Home', href: home.url() },
        ...(breadcrumbs.length > 0
            ? breadcrumbs
            : [{ title: 'Dashboard', href: '/dashboard' }]),
    ];

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <StorefrontHeader />
            <main className="flex-1">
                <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                    <nav
                        aria-label="Breadcrumb"
                        className="text-muted-foreground mb-6 text-sm"
                    >
                        <ol className="flex flex-wrap items-center gap-1.5">
                            {trail.map((item, index) => {
                                const isLast = index === trail.length - 1;

                                return (
                                    <li
                                        key={`${item.href}-${item.title}`}
                                        className="flex items-center gap-1.5"
                                    >
                                        {index > 0 ? (
                                            <span aria-hidden>›</span>
                                        ) : null}
                                        {isLast ? (
                                            <span className="text-foreground">
                                                {item.title}
                                            </span>
                                        ) : (
                                            <Link
                                                href={item.href}
                                                prefetch
                                                className="hover:text-foreground transition-colors"
                                            >
                                                {item.title}
                                            </Link>
                                        )}
                                    </li>
                                );
                            })}
                        </ol>
                    </nav>

                    <h1 className="text-foreground mb-8 text-center text-3xl font-semibold tracking-tight sm:mb-10 sm:text-4xl">
                        My Account
                    </h1>

                    <div className="grid gap-10 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-14">
                        <AccountSidebar className="lg:pt-1" />
                        <div className="min-w-0">{children}</div>
                    </div>
                </div>
            </main>
            <StorefrontFooter />
        </div>
    );
}
