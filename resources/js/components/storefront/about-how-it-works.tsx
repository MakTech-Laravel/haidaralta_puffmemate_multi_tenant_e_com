import { aboutHowItWorks } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';

type AboutHowItWorksProps = {
    className?: string;
};

export function AboutHowItWorks({ className }: AboutHowItWorksProps) {
    return (
        <section className={cn('bg-muted/40 py-16 sm:py-20', className)}>
            <div className="mx-auto container px-4 sm:px-6">
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-primary text-xs font-extrabold tracking-[0.14em] uppercase">
                        {aboutHowItWorks.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        {aboutHowItWorks.heading}
                    </h2>
                    <p className="text-muted-foreground mt-4 text-sm leading-relaxed sm:text-base">
                        {aboutHowItWorks.body}
                    </p>
                </div>

                <ul className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {aboutHowItWorks.steps.map((step) => {
                        const Icon = step.icon;

                        return (
                            <li
                                key={step.number}
                                className="border-border bg-background relative overflow-hidden rounded-xl border p-5"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-full">
                                        <Icon className="size-5" aria-hidden />
                                    </span>
                                    <span
                                        className="text-muted-foreground/25 text-4xl font-bold tabular-nums"
                                        aria-hidden
                                    >
                                        {step.number}
                                    </span>
                                </div>
                                <h3 className="text-foreground mt-4 text-base font-bold">
                                    {step.number} {step.title}
                                </h3>
                                <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                    {step.description}
                                </p>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </section>
    );
}
