type SpecificationRow = {
    label: string;
    value: string;
};

type ProductSpecificationsProps = {
    rows: SpecificationRow[];
};

export function ProductSpecifications({ rows }: ProductSpecificationsProps) {
    return (
        <section className="bg-muted flex flex-col gap-4 rounded-3xl p-5 sm:p-8">
            <h2 className="text-foreground text-xl font-semibold tracking-tight">
                Specifications
            </h2>
            <div className="bg-card overflow-hidden rounded-2xl">
                <table className="w-full text-left text-sm">
                    <thead className="bg-card">
                        <tr>
                            <th className="text-muted-foreground font-inter px-4 py-3 text-xs font-semibold tracking-wide uppercase">
                                Specification
                            </th>
                            <th className="text-muted-foreground font-inter px-4 py-3 text-xs font-semibold tracking-wide uppercase">
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
