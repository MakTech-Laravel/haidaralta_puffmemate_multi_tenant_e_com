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
                            <th className="text-primary px-4 py-3 font-bold font-inter">
                                Specification
                            </th>
                            <th className="text-primary px-4 py-3 font-bold font-inter">
                                Details
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.label} className="border-border border-t">
                                <td className="text-muted-foreground px-4 py-3 font-normal font-inter text-sm">
                                    {row.label}
                                </td>
                                <td className="text-foreground px-4 py-3 font-medium font-inter text-sm">
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
