import { Link } from '@inertiajs/react';
import { BadgeCheck, Home } from 'lucide-react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { privacyPage } from '@/lib/storefront-privacy';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type PrivacyIntroProps = {
    className?: string;
};

export function PrivacyIntro({ className }: PrivacyIntroProps) {
    return (
        <section className={cn('bg-background pt-8 pb-6 sm:pt-10', className)}>
            <div className="mx-auto container max-w-3xl px-4 sm:px-6">
                <Breadcrumb>
                    <BreadcrumbList className="text-muted-foreground gap-1.5 text-xs sm:text-sm">
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link
                                    href={home()}
                                    prefetch
                                    className="hover:text-primary inline-flex items-center gap-1.5"
                                >
                                    <Home className="size-3.5" aria-hidden />
                                    Home
                                </Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator>/</BreadcrumbSeparator>
                        <BreadcrumbItem>
                            <span>Legal & Governance</span>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator>/</BreadcrumbSeparator>
                        <BreadcrumbItem>
                            <BreadcrumbPage className="text-primary font-semibold">
                                {privacyPage.title}
                            </BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <p className="text-primary mt-8 text-[0.7rem] font-bold tracking-[0.12em] uppercase sm:text-xs">
                    {privacyPage.eyebrow}
                </p>

                <h1 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">
                    {privacyPage.title}
                </h1>

                <p className="text-muted-foreground mt-5 max-w-2xl text-sm leading-relaxed sm:text-base">
                    {privacyPage.intro}
                </p>

                <div className="text-muted-foreground mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs sm:text-sm">
                    <span className="inline-flex items-center gap-1.5">
                        <BadgeCheck
                            className="text-primary size-4 shrink-0"
                            aria-hidden
                        />
                        <span className="text-foreground/80">
                            {privacyPage.versionLabel}
                        </span>
                    </span>
                    <span className="text-muted-foreground/80">
                        {privacyPage.updatedAt}
                    </span>
                </div>

                <p className="text-muted-foreground mt-2 text-xs sm:text-sm">
                    {privacyPage.applicableEntity}
                </p>

                <hr className="border-border mt-8" />

                <ul className="mt-8 space-y-6">
                    {privacyPage.summaryPoints.map((point) => (
                        <li key={point.title} className="flex items-start gap-2.5">
                            <span
                                className="bg-primary mt-2 size-1.5 shrink-0 rounded-full"
                                aria-hidden
                            />
                            <div className="min-w-0">
                                <a
                                    href={`#${point.id}`}
                                    className="text-primary hover:text-primary/80 text-sm font-bold transition-colors sm:text-base"
                                >
                                    {point.title}
                                </a>
                                <p className="text-muted-foreground mt-1 text-sm leading-relaxed">
                                    {point.body}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
