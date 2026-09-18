import { aboutMultiBranch } from '@/lib/storefront-about';
import { cn } from '@/lib/utils';

type AboutMultiBranchProps = {
    className?: string;
};

export function AboutMultiBranch({ className }: AboutMultiBranchProps) {
    return (
        <section
            className={cn(
                'bg-foreground text-primary-foreground py-16 sm:py-20',
                className,
            )}
        >
            <div className="container mx-auto grid items-start gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-14">
                <div>
                    <p className="text-primary text-xs font-semibold tracking-[0.14em] uppercase">
                        {aboutMultiBranch.eyebrow}
                    </p>
                    <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                        {aboutMultiBranch.heading}
                    </h2>
                    <p className="font-inter mt-4 max-w-xl text-sm leading-relaxed font-normal text-white/75 sm:text-lg">
                        {aboutMultiBranch.body}
                    </p>
                    <p className="font-inter mt-4 max-w-xl text-sm leading-relaxed font-normal text-white/75 sm:text-lg">
                        {aboutMultiBranch.body}
                    </p>
                    <div className="border-primary/60 bg-primary/10 font-inter mt-8 rounded-lg border px-4 py-3 text-base leading-relaxed font-medium">
                        <p className="text-primary font-inter text-sm leading-relaxed font-medium tracking-[0.10em] uppercase">
                            Important
                        </p>
                        {aboutMultiBranch.promise}
                    </div>
                </div>

                <ol className="space-y-4 rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                    <p className="text-muted-foreground font-inter text-sm leading-relaxed font-semibold tracking-[0.10em] uppercase">
                        Branch Routing
                    </p>
                    {aboutMultiBranch.factors.map((factor) => (
                        <li
                            key={factor.number}
                            className="flex items-start gap-4 border-b border-white/10 pb-4 last:border-b-0 last:pb-0"
                        >
                            <span className="bg-primary text-primary-foreground font-inter flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                                {factor.number}
                            </span>
                            <div>
                                <h3 className="font-inter text-lg font-bold">
                                    {factor.title}
                                </h3>
                                <p className="mt-1 text-sm leading-relaxed text-white/65">
                                    {factor.description}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
