type SpecificationRow = {
    label: string;
    value: string;
};

type ProductSpecificationsProps = {
    rows: SpecificationRow[];
};

export function ProductSpecifications({ rows }: ProductSpecificationsProps) {
    return (
        <section className="flex flex-col gap-4">
            <h2 className="text-foreground text-xl font-bold tracking-tight uppercase">
                Specifications
            </h2>
            <div className="border-border overflow-hidden rounded-xl border">
                <table className="w-full text-left text-sm">
                    <thead className="bg-muted">
                        <tr>
                            <th className="text-foreground px-4 py-3 font-semibold">
                                Specification
                            </th>
                            <th className="text-foreground px-4 py-3 font-semibold">
                                Details
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.label} className="border-border border-t">
                                <td className="text-muted-foreground px-4 py-3">
                                    {row.label}
                                </td>
                                <td className="text-foreground px-4 py-3 font-medium">
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
