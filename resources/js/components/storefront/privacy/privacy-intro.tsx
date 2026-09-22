import { Link } from '@inertiajs/react';
import { BadgeCheck, Home } from 'lucide-react';
import { AppDate } from '@/components/app-datetime';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type PrivacyIntroData = {
    eyebrow: string;
    intro: string;
    versionLabel: string;
    updatedAt: string;
    applicableEntity: string;
    summaryPoints: Array<{ id: string; title: string; body: string }>;
};

type PrivacyIntroProps = {
    className?: string;
    title: string;
    data: PrivacyIntroData;
};

function isIsoDate(value: string): boolean {
    return /^\d{4}-\d{2}-\d{2}$/.test(value.trim());
}

export function PrivacyIntro({ className, title, data }: PrivacyIntroProps) {
    const updatedAt = data.updatedAt?.trim() ?? '';

    return (
        <section className={cn('bg-background pt-8 pb-6 sm:pt-10', className)}>
            <div className="container mx-auto max-w-3xl px-4 sm:px-6">
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
                                {title}
                            </BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <p className="text-primary mt-8 text-[0.7rem] font-bold tracking-[0.12em] uppercase sm:text-xs">
                    {data.eyebrow}
                </p>

                <h1 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">
                    {title}
                </h1>

                <p className="text-muted-foreground mt-5 max-w-2xl text-sm leading-relaxed sm:text-base">
                    {data.intro}
                </p>

                <div className="text-muted-foreground mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs sm:text-sm">
                    <span className="inline-flex items-center gap-1.5">
                        <BadgeCheck
                            className="text-primary size-4 shrink-0"
                            aria-hidden
                        />
                        <span className="text-foreground/80">
                            {data.versionLabel}
                        </span>
                    </span>
                    <span className="text-muted-foreground/80 inline-flex items-center gap-1">
                        {updatedAt ? (
                            isIsoDate(updatedAt) ? (
                                <>
                                    Last Updated: <AppDate value={updatedAt} />
                                </>
                            ) : (
                                updatedAt
                            )
                        ) : null}
                    </span>
                </div>

                <p className="text-muted-foreground mt-2 text-xs sm:text-sm">
                    {data.applicableEntity}
                </p>

                <hr className="border-border mt-8" />

                {data.summaryPoints.length > 0 ? (
                    <ul className="mt-8 space-y-6">
                        {data.summaryPoints.map((point) => (
                            <li
                                key={point.id || point.title}
                                className="flex items-start gap-2.5"
                            >
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
                ) : null}
            </div>
        </section>
    );
}
