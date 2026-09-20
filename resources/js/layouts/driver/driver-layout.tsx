import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DriverHeader } from '@/components/driver/driver-header';
import { DriverMobileNav } from '@/components/driver/driver-mobile-nav';
import { DriverSidebar } from '@/components/driver/driver-sidebar';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes/driver';
import type { BreadcrumbItem } from '@/types';

export default function DriverLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    const trail: BreadcrumbItem[] = [
        { title: 'Home', href: dashboard.url() },
        ...(breadcrumbs.length > 0
            ? breadcrumbs
            : [{ title: 'Dashboard', href: dashboard.url() }]),
    ];

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <DriverHeader />

            <main className="flex-1 pb-[calc(4.5rem+env(safe-area-inset-bottom))] xl:pb-0">
                <div className="mx-auto w-full max-w-5xl px-4 py-5 sm:px-6 sm:py-8 xl:max-w-6xl xl:py-10">
                    <nav
                        aria-label="Breadcrumb"
                        className="text-muted-foreground mb-8 hidden text-base xl:mb-10 xl:block"
                    >
                        <ol className="flex flex-wrap items-center gap-1.5">
                            {trail.map((item, index) => {
                                const isLast = index === trail.length - 1;

                                return (
                                    <li
                                        key={`${toUrl(item.href)}-${item.title}`}
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
                        <div className="border-border bg-border mt-4 h-0.5 w-full" />
                    </nav>

                    <div className="grid gap-8 xl:grid-cols-[13rem_minmax(0,1fr)] xl:gap-12">
                        <DriverSidebar className="xl:sticky xl:top-24 xl:self-start" />
                        <div className="min-w-0">{children}</div>
                    </div>
                </div>
            </main>

            <DriverMobileNav />
        </div>
    );
}
