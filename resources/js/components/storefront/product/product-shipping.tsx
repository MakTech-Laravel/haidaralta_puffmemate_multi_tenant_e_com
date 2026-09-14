type ProductShippingProps = {
    points: string[];
};

export function ProductShipping({ points }: ProductShippingProps) {
    return (
        <section className="bg-accent flex flex-col gap-4 p-5">
            <h2 className="text-foreground text-2xl font-semibold uppercase font-inter">
                Shipping & Delivery
            </h2>

            <h4 className="text-foreground text-base font-medium font-inter">Delivery Availability</h4>

            <ol className="text-foreground list-decimal space-y-2 pl-5 text-base leading-relaxed font-normal font-inter">
                {points.map((point) => (
                    <li key={point}>{point}</li>
                ))}
            </ol>
        </section>
    );
}
