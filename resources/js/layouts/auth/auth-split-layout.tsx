import { Link } from '@inertiajs/react';
import { useAppBrand } from '@/hooks/use-app-brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const brand = useAppBrand();

    return (
        <div className="bg-background grid min-h-dvh lg:grid-cols-2">
            <aside className="relative isolate overflow-x-hidden bg-black px-5 py-4 text-white sm:px-8 sm:py-5 lg:flex lg:min-h-dvh lg:flex-col lg:justify-between lg:overflow-hidden lg:px-12 lg:py-14">
                <div
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,color-mix(in_oklab,var(--primary)_38%,transparent),transparent_55%),linear-gradient(145deg,transparent_40%,color-mix(in_oklab,var(--primary)_18%,transparent))] opacity-90 motion-safe:animate-in motion-safe:fade-in motion-safe:duration-700"
                    aria-hidden
                />
                <div
                    className="bg-primary pointer-events-none absolute -right-16 -bottom-20 hidden size-56 rotate-12 rounded-3xl opacity-90 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-4 motion-safe:duration-700 lg:block lg:size-72"
                    aria-hidden
                />

                <div className="relative z-10 flex flex-col gap-3 lg:flex-1 lg:justify-center lg:gap-8 lg:py-4">
                    <p className="text-primary hidden text-xs font-semibold tracking-[0.16em] uppercase sm:text-sm lg:block">
                        Australia · Multi-branch
                    </p>

                    <Link
                        href={home()}
                        prefetch
                        className="inline-flex w-fit max-w-full"
                    >
                        <img
                            src={brand.logoUrl}
                            alt={brand.name}
                            className={
                                brand.hasCustomLogo
                                    ? 'h-10 w-auto max-w-full object-contain object-left sm:h-12 lg:h-14'
                                    : 'h-10 w-auto max-w-full bg-white object-contain object-left sm:h-12 lg:h-14'
                            }
                        />
                    </Link>

                    <div className="bg-primary h-0.5 w-10 lg:h-1 lg:w-14" aria-hidden />

                    <p className="font-dm-sans hidden max-w-sm text-sm leading-relaxed text-white/85 sm:text-base lg:block">
                        Sign in to order, deliver, or run the desk — one account
                        entry for every portal.
                    </p>
                </div>

                <p className="text-primary relative z-10 mt-8 hidden text-xs font-semibold tracking-[0.14em] uppercase lg:block">
                    Shop · Deliver · Operate
                </p>
            </aside>

            <div className="bg-background flex flex-1 flex-col justify-center px-5 py-6 sm:px-10 sm:py-8 lg:px-14 lg:py-14">
                <div className="mx-auto w-full max-w-md">
                    <div className="mb-5 space-y-1.5 sm:mb-8 sm:space-y-2">
                        <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                            Account
                        </p>
                        {title ? (
                            <h2 className="text-foreground text-2xl font-bold tracking-tight sm:text-3xl">
                                {title}
                            </h2>
                        ) : null}
                        {description ? (
                            <p className="text-muted-foreground text-sm leading-relaxed sm:text-base">
                                {description}
                            </p>
                        ) : null}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
