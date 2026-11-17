import { useMemo } from "react";
import { CartesianGrid, Line, LineChart, XAxis, YAxis } from "recharts";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    type ChartConfig,
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";

export type TimelinePoint = {
    label: string;
    date: string;
    orders: number;
    delivered: number;
};

type TimelineReport = {
    label: string;
    range: string | null;
    timeline: TimelinePoint[];
};

type Row = {
    label: string;
    compareLabel: string | null;
    orders: number | null;
    delivered: number | null;
    compare_orders: number | null;
    compare_delivered: number | null;
};

const SERIES_UNITS: Record<string, string> = {
    orders: "orders",
    compare_orders: "orders",
    delivered: "delivered",
    compare_delivered: "delivered",
};

/**
 * "Orders and deliveries" section: shadcn Line Chart - Multiple with
 * Total orders and Delivered lines; compare adds dashed lines for the
 * compare period aligned by position (day 1 vs day 1).
 */
export function OrderReportTimelineCard({
    report,
    compare,
}: {
    report: TimelineReport;
    compare: TimelineReport | null;
}) {
    const isMonthly = report.timeline.length > 0 && report.range === "1y";

    const data = useMemo<Row[]>(() => {
        const length = Math.max(
            report.timeline.length,
            compare?.timeline.length ?? 0,
        );

        return Array.from({ length }, (_, index) => {
            const point = report.timeline[index];
            const comparePoint = compare?.timeline[index];

            return {
                label: point?.label ?? comparePoint?.label ?? "",
                compareLabel: comparePoint?.label ?? null,
                orders: point?.orders ?? null,
                delivered: point?.delivered ?? null,
                compare_orders: comparePoint?.orders ?? null,
                compare_delivered: comparePoint?.delivered ?? null,
            };
        });
    }, [report.timeline, compare]);

    const config = useMemo(
        () =>
            ({
                orders: {
                    label: compare
                        ? `Total orders · ${report.label}`
                        : "Total orders",
                    color: "var(--chart-1)",
                },
                delivered: {
                    label: compare
                        ? `Delivered · ${report.label}`
                        : "Delivered",
                    color: "var(--chart-2)",
                },
                compare_orders: {
                    label: `Total orders · ${compare?.label ?? ""}`,
                    color: "var(--chart-1)",
                },
                compare_delivered: {
                    label: `Delivered · ${compare?.label ?? ""}`,
                    color: "var(--chart-2)",
                },
            }) satisfies ChartConfig,
        [report.label, compare],
    );

    const hasData = data.some(
        (row) =>
            (row.orders ?? 0) > 0 ||
            (row.delivered ?? 0) > 0 ||
            (row.compare_orders ?? 0) > 0 ||
            (row.compare_delivered ?? 0) > 0,
    );

    return (
        <Card className="shadow-none">
            <CardHeader>
                <div className="space-y-1">
                    <CardTitle>Orders and deliveries</CardTitle>
                    <CardDescription>
                        {isMonthly ? "Monthly" : "Daily"} orders and deliveries
                        for {report.label}
                        {compare ? `, compared with ${compare.label}` : ""}.
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                {!hasData ? (
                    <p className="text-muted-foreground text-sm">
                        No orders in this period.
                    </p>
                ) : (
                    <ChartContainer
                        config={config}
                        className="aspect-auto h-72 w-full"
                    >
                        <LineChart
                            accessibilityLayer
                            data={data}
                            margin={{ left: 12, right: 12, top: 8 }}
                        >
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="label"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={24}
                            />
                            <YAxis
                                allowDecimals={false}
                                tickLine={false}
                                axisLine={false}
                                width={32}
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent
                                        labelFormatter={(_label, payload) => {
                                            const row = payload?.[0]
                                                ?.payload as Row | undefined;

                                            if (!row) {
                                                return "";
                                            }

                                            return compare && row.compareLabel
                                                ? `${row.label} vs ${row.compareLabel}`
                                                : row.label;
                                        }}
                                        formatter={(value, name, item) => (
                                            <div className="flex w-full items-center justify-between gap-4">
                                                <span className="flex items-center gap-2">
                                                    <span
                                                        className="size-2.5 shrink-0 rounded-[2px]"
                                                        style={{
                                                            backgroundColor:
                                                                item.color,
                                                        }}
                                                    />
                                                    <span className="text-muted-foreground">
                                                        {config[
                                                            name as keyof typeof config
                                                        ]?.label ?? name}
                                                    </span>
                                                </span>
                                                <span className="text-foreground font-medium tabular-nums">
                                                    {Number(
                                                        value,
                                                    ).toLocaleString()}{" "}
                                                    {SERIES_UNITS[
                                                        String(name)
                                                    ] ?? ""}
                                                </span>
                                            </div>
                                        )}
                                    />
                                }
                            />
                            <ChartLegend content={<ChartLegendContent />} />
                            <Line
                                dataKey="orders"
                                type="monotone"
                                stroke="var(--color-orders)"
                                strokeWidth={2}
                                dot={false}
                            />
                            <Line
                                dataKey="delivered"
                                type="monotone"
                                stroke="var(--color-delivered)"
                                strokeWidth={2}
                                dot={false}
                            />
                            {compare ? (
                                <>
                                    <Line
                                        dataKey="compare_orders"
                                        type="monotone"
                                        stroke="var(--color-compare_orders)"
                                        strokeWidth={2}
                                        strokeDasharray="4 4"
                                        strokeOpacity={0.6}
                                        dot={false}
                                    />
                                    <Line
                                        dataKey="compare_delivered"
                                        type="monotone"
                                        stroke="var(--color-compare_delivered)"
                                        strokeWidth={2}
                                        strokeDasharray="4 4"
                                        strokeOpacity={0.6}
                                        dot={false}
                                    />
                                </>
                            ) : null}
                        </LineChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}
