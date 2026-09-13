type ProductShippingProps = {
    points: string[];
};

export function ProductShipping({ points }: ProductShippingProps) {
    return (
        <section className="flex flex-col gap-4">
            <h2 className="text-foreground text-xl font-bold tracking-tight uppercase">
                Shipping & Delivery
            </h2>
            <ol className="text-muted-foreground list-decimal space-y-2 pl-5 text-sm leading-relaxed">
                {points.map((point) => (
                    <li key={point}>{point}</li>
                ))}
            </ol>
        </section>
    );
}
