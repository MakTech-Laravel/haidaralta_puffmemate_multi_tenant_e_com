import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DriverHeader } from '@/components/driver/driver-header';
import { DriverSidebar } from '@/components/driver/driver-sidebar';
import type { BreadcrumbItem } from '@/types';

export default function DriverLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    const trail: BreadcrumbItem[] = [
        { title: 'Home', href: '/driver/dashboard' },
        ...(breadcrumbs.length > 0
            ? breadcrumbs
            : [{ title: 'Dashboard', href: '/driver/dashboard' }]),
    ];

    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <DriverHeader />

            <main className="flex-1">
                <h1 className="bg-accent text-foreground py-4 text-center text-2xl font-bold tracking-tight sm:py-8 sm:text-3xl md:text-[40px]">
                    Driver
                </h1>

                <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                    <nav
                        aria-label="Breadcrumb"
                        className="text-muted-foreground mb-6 text-base sm:mb-10 md:mb-14"
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
                        <div className="border-border mt-4 hidden h-0.5 w-full bg-border lg:block" />
                    </nav>

                    <div className="grid gap-8 lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-12">
                        <DriverSidebar className="lg:sticky lg:top-28 lg:self-start lg:pt-1" />
                        <div className="min-w-0">{children}</div>
                    </div>
                </div>
            </main>

            <footer className="border-border text-muted-foreground border-t py-4 text-center text-xs">
                PuffMeMate Driver · Field deliveries
            </footer>
        </div>
    );
}
