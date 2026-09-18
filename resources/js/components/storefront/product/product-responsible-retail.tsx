type ProductResponsibleRetailProps = {
    points: string[];
};

export function ProductResponsibleRetail({
    points,
}: ProductResponsibleRetailProps) {
    return (
        <section className="bg-accent flex flex-col gap-4 p-5">
            <h2 className="text-foreground font-inter text-2xl font-semibold uppercase">
                Responsible Retail
            </h2>
            <p className="text-primary font-inter text-lg font-bold">
                For Adults 18+ Only
            </p>
            <ol className="text-foreground font-inter list-decimal space-y-2 pl-5 text-base leading-relaxed font-normal">
                {points.map((point) => (
                    <li key={point}>{point}</li>
                ))}
            </ol>
        </section>
    );
}
