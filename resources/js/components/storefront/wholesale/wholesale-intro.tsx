import { cn } from '@/lib/utils';

type WholesaleIntroProps = {
    className?: string;
    data: {
        title: string;
        body: string;
        benefits: Array<{ title: string; description: string }>;
    };
};

export function WholesaleIntro({ className, data }: WholesaleIntroProps) {
    return (
        <section className={cn('bg-background py-8 sm:py-14', className)}>
            <div className="container mx-auto px-4 sm:px-6">
                <div className="max-w-6xl">
                    <h1 className="text-foreground text-2xl font-semibold tracking-tight uppercase">
                        {data.title}
                    </h1>
                    <p className="text-muted-foreground font-inter mt-3 text-base leading-relaxed font-normal sm:mt-4 sm:text-lg">
                        {data.body}
                    </p>
                </div>

                <ul className="mt-10 grid gap-8 sm:grid-cols-2 lg:mt-12 lg:grid-cols-4 lg:gap-6">
                    {data.benefits.map((benefit) => (
                        <li key={benefit.title}>
                            <h2 className="text-foreground text-base font-semibold tracking-tight sm:text-lg">
                                {benefit.title}
                            </h2>
                            <p className="text-muted-foreground mt-2 text-base leading-relaxed">
                                {benefit.description}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
