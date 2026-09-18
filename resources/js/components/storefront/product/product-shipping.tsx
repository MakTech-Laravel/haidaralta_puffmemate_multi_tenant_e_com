type ProductShippingProps = {
    points: string[];
};

export function ProductShipping({ points }: ProductShippingProps) {
    return (
        <section className="bg-accent flex flex-col gap-4 p-5">
            <h2 className="text-foreground font-inter text-2xl font-semibold uppercase">
                Shipping & Delivery
            </h2>

            <h4 className="text-foreground font-inter text-base font-medium">
                Delivery Availability
            </h4>

            <ol className="text-foreground font-inter list-decimal space-y-2 pl-5 text-base leading-relaxed font-normal">
                {points.map((point) => (
                    <li key={point}>{point}</li>
                ))}
            </ol>
        </section>
    );
}
