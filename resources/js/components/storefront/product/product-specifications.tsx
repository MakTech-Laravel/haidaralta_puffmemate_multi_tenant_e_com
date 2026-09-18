type SpecificationRow = {
    label: string;
    value: string;
};

type ProductSpecificationsProps = {
    rows: SpecificationRow[];
};

export function ProductSpecifications({ rows }: ProductSpecificationsProps) {
    return (
        <section className="bg-accent flex flex-col gap-4 p-5">
            <h2 className="text-foreground text-xl font-bold tracking-tight uppercase">
                Specifications
            </h2>
            <div className="overflow-hidden">
                <table className="w-full text-left text-sm">
                    <thead className="bg-primary/15">
                        <tr>
                            <th className="text-primary font-inter px-4 py-3 font-bold">
                                Specification
                            </th>
                            <th className="text-primary font-inter px-4 py-3 font-bold">
                                Details
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr
                                key={row.label}
                                className="border-border border-t"
                            >
                                <td className="text-muted-foreground font-inter px-4 py-3 text-sm font-normal">
                                    {row.label}
                                </td>
                                <td className="text-foreground font-inter px-4 py-3 text-sm font-medium">
                                    {row.value}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
