import { Link } from '@inertiajs/react';
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
            <div className="mx-auto container max-w-4xl px-4 sm:px-6">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link
                                    href={home()}
                                    prefetch
                                    className="hover:text-primary"
                                >
                                    Home
                                </Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <span className="text-muted-foreground">
                                Information
                            </span>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage className="text-primary font-medium">
                                {privacyPage.title}
                            </BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <h1 className="text-foreground mt-6 text-3xl font-bold tracking-tight sm:text-4xl">
                    {privacyPage.title}
                </h1>

                <div className="text-muted-foreground mt-5 space-y-4 text-sm leading-relaxed sm:text-base">
                    {privacyPage.intro.map((paragraph) => (
                        <p key={paragraph}>{paragraph}</p>
                    ))}
                </div>

                <div className="mt-8">
                    <h2 className="text-foreground text-sm font-bold tracking-wide uppercase">
                        {privacyPage.quickLinksLabel}
                    </h2>
                    <ul className="mt-3 space-y-2">
                        {privacyPage.quickLinks.map((link) => (
                            <li key={link.id} className="flex items-start gap-2">
                                <span
                                    className="bg-primary mt-2 size-1.5 shrink-0 rounded-full"
                                    aria-hidden
                                />
                                <a
                                    href={`#${link.id}`}
                                    className="text-foreground hover:text-primary text-sm font-medium transition-colors"
                                >
                                    {link.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="bg-primary/5 border-primary/10 mt-8 rounded-xl border px-5 py-5 sm:px-6">
                    <h2 className="text-foreground text-sm font-bold tracking-wide uppercase">
                        {privacyPage.summaryTitle}
                    </h2>
                    <ul className="mt-3 space-y-2.5">
                        {privacyPage.summary.map((item) => (
                            <li
                                key={item}
                                className="text-muted-foreground flex items-start gap-2 text-sm leading-relaxed"
                            >
                                <span
                                    className="bg-primary mt-2 size-1.5 shrink-0 rounded-full"
                                    aria-hidden
                                />
                                <span>{item}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}
