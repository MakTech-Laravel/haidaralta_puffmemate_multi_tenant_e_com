type ProductResponsibleRetailProps = {
    points: string[];
};

export function ProductResponsibleRetail({
    points,
}: ProductResponsibleRetailProps) {
    return (
        <section className="flex flex-col gap-4">
            <h2 className="text-foreground text-xl font-bold tracking-tight uppercase">
                Responsible Retail
            </h2>
            <p className="text-primary text-base font-bold">
                For Adults 18+ Only
            </p>
            <ol className="text-muted-foreground list-decimal space-y-2 pl-5 text-sm leading-relaxed">
                {points.map((point) => (
                    <li key={point}>{point}</li>
                ))}
            </ol>
        </section>
    );
}
// gp k 195B