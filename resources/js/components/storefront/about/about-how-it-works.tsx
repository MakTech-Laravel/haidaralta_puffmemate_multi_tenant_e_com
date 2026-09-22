import { resolveLucideIcon } from '@/lib/cms-icons';
import { cn } from '@/lib/utils';

type AboutHowItWorksData = {
    eyebrow: string;
    heading: string;
    body: string;
    steps: {
        number: string;
        title: string;
        description: string;
        icon: string;
    }[];
};

type AboutHowItWorksProps = {
    data: AboutHowItWorksData;
    className?: string;
};

export function AboutHowItWorks({ data, className }: AboutHowItWorksProps) {
    return (
        <section className={cn('bg-muted/40 py-16 sm:py-20', className)}>
            <div className="container mx-auto px-4 sm:px-6">
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-primary text-xs font-semibold tracking-[0.14em] uppercase">
                        {data.eyebrow}
                    </p>
                    <h2 className="text-foreground mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                        {data.heading}
                    </h2>
                    <p className="text-muted-foreground font-inter mt-4 text-sm leading-relaxed font-normal sm:text-lg">
                        {data.body}
                    </p>
                </div>

                <ul className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {data.steps.map((step) => {
                        const Icon = resolveLucideIcon(step.icon);

                        return (
                            <li
                                key={step.number}
                                className="border-border bg-background relative overflow-hidden rounded-2xl border p-5"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-full">
                                        <Icon className="size-6" aria-hidden />
                                    </span>
                                    <span
                                        className="text-muted-foreground/25 text-3xl font-extrabold tabular-nums"
                                        aria-hidden
                                    >
                                        {step.number}
                                    </span>
                                </div>
                                <h3 className="text-foreground font-inter mt-4 text-lg font-bold">
                                    {step.title}
                                </h3>
                                <p className="text-muted-foreground font-inter mt-2 text-sm leading-relaxed font-normal">
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
