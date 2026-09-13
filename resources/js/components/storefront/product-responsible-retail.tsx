type ProductResponsibleRetailProps = {
    points: string[];
};

export function ProductResponsibleRetail({
    points,
}: ProductResponsibleRetailProps) {
    return (
        <section className="bg-accent flex flex-col gap-4 p-5">
            <h2 className="text-foreground text-2xl font-semibold uppercase font-inter">
                Responsible Retail
            </h2>
            <p className="text-primary text-lg font-bold font-inter">
                For Adults 18+ Only
            </p>
            <ol className="text-foreground list-decimal space-y-2 pl-5 text-base leading-relaxed font-normal font-inter">
                {points.map((point) => (
                    <li key={point}>{point}</li>
                ))}
            </ol>
        </section>
    );
}
// gp k 195B