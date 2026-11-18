import { Head, router } from "@inertiajs/react";
import { ArrowDown, ArrowUp, Minus } from "lucide-react";
import { useMemo } from "react";
import {
    Bar,
    BarChart,
    CartesianGrid,
    LabelList,
    Pie,
    PieChart,
    PolarAngleAxis,
    RadialBar,
    RadialBarChart,
    Rectangle,
    type BarShapeProps,
    XAxis,
    YAxis,
} from "recharts";
import OrderReportController from "@/actions/App/Http/Controllers/Platform/Reports/OrderReportController";
import { ExportDropdown } from "@/components/export/export-dropdown";
import Heading from "@/components/heading";
import { OrderReportFilters } from "@/components/platform/order-report-filters";
import {
    OrderReportTimelineCard,
    type TimelinePoint,
} from "@/components/platform/order-report-timeline";
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
import { cn } from "@/lib/utils";

type ProductRow = {
    name: string;
    sold: number;
};

type BranchRow = {
    id: number;
    branch: string;
    name: string;
    sales: number;
};

type DriverRow = {
    id: number;
    driver: string;
    name: string;
    deliveries: number;
};

type Summary = {
    orders: number;
    delivered: number;
    waiting: number;
    failed: number;
    cancelled: number;
    refunded: number;
    refunded_amount: number;
    sales: number;
    payments_collected: number;
    cod_collected: number;
};

type MonthReport = {
    month: string | null;
    range: string | null;
    label: string;
    summary: Summary;
    products: ProductRow[];
    branches: BranchRow[];
    drivers: DriverRow[];
    product_comparison?: ProductComparisonRow[];
    timeline: TimelinePoint[];
};

type Dashboard = MonthReport & {
    compare: MonthReport | null;
    trend: { label: string; summary: Summary };
};

function changeLabel(current: number, previous: number): string {
    const diff = current - previous;

    if (previous === 0) {
        return diff === 0 ? "No change" : "New vs. 0";
    }

    const percent = Math.round((diff / previous) * 1000) / 10;

    return `${diff > 0 ? "+" : ""}${percent}%`;
}

const CHART_COLORS = [
    "var(--chart-1)",
    "var(--chart-2)",
    "var(--chart-3)",
    "var(--chart-4)",
    "var(--chart-5)",
] as const;

const productChartConfig = {
    sold: {
        label: "Units sold",
        color: "var(--chart-1)",
    },
} satisfies ChartConfig;

function formatMoney(value: number): string {
    return `$${value.toLocaleString()}`;
}

function formatCompactMoney(value: number): string {
    const abs = Math.abs(value);
    const sign = value < 0 ? "-" : "";

    const withSuffix = (scaled: number, suffix: string): string => {
        const rounded = Math.round(scaled * 10) / 10;
        const text = Number.isInteger(rounded)
            ? String(rounded)
            : rounded.toFixed(1).replace(/\.0$/, "");

        return `${sign}$${text}${suffix}`;
    };

    if (abs >= 1_000_000_000) {
        return withSuffix(abs / 1_000_000_000, "B");
    }

    if (abs >= 1_000_000) {
        return withSuffix(abs / 1_000_000, "m");
    }

    if (abs >= 1_000) {
        return withSuffix(abs / 1_000, "k");
    }

    return formatMoney(value);
}

function formatCount(value: number): string {
    return value.toLocaleString();
}

function buildNamedChartConfig(
    rows: Array<{ key: string; label: string }>,
): ChartConfig {
    const config: ChartConfig = {};

    rows.forEach((row, index) => {
        config[row.key] = {
            label: row.label,
            color: CHART_COLORS[index % CHART_COLORS.length],
        };
    });

    return config;
}

function chartColor(
    config: ChartConfig,
    key: string,
    fallback = "var(--chart-1)",
): string {
    const entry = config[key];

    return entry && "color" in entry && entry.color ? entry.color : fallback;
}

/**
 * Shared tooltip body: what the item is, which period, and the metric with its unit.
 */
function ReportTooltipBody({
    name,
    period,
    label,
    value,
}: {
    name: string;
    period: string;
    label: string;
    value: string;
}) {
    return (
        <div className="grid min-w-40 gap-1">
            <p className="text-foreground font-semibold">{name}</p>
            <p className="text-muted-foreground text-xs">{period}</p>
            <div className="flex items-center justify-between gap-4">
                <span className="text-muted-foreground">{label}</span>
                <span className="text-foreground font-medium tabular-nums">
                    {value}
                </span>
            </div>
        </div>
    );
}

type ProductComparisonRow = {
    name: string;
    current: number;
    previous: number;
};

/**
 * Bar that skips rendering for a zero value (no flat stub); its label still
 * shows the 0 and the slot stays reserved so the pair keeps its position.
 */
function NonZeroBar(props: BarShapeProps) {
    if (!Number(props.value)) {
        return <g />;
    }

    return <Rectangle {...props} radius={4} />;
}

function ProductsChartCard({
    label,
    products,
    compareLabel,
    comparison,
}: {
    label: string;
    products: ProductRow[];
    compareLabel?: string;
    comparison?: ProductComparisonRow[];
}) {
    const isComparing = compareLabel !== undefined && comparison !== undefined;
    const comparisonData = comparison ?? [];

    const comparisonConfig = useMemo(
        () =>
            ({
                current: {
                    label: label,
                    color: "var(--chart-1)",
                },
                previous: {
                    label: compareLabel ? compareLabel : "",
                    color: "var(--chart-2)",
                },
            }) satisfies ChartConfig,
        [label, compareLabel],
    );

    const hasData = isComparing
        ? comparisonData.length > 0
        : products.length > 0;

    return (
        <Card className="shadow-none">
            <CardHeader>
                <div className="space-y-1">
                    <CardTitle>Top 5 selling products</CardTitle>
                    <CardDescription>
                        {isComparing
                            ? `Units sold in ${label} compared with ${compareLabel}.`
                            : `Products customers bought the most in ${label}.`}
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                {!hasData ? (
                    <p className="text-muted-foreground text-sm">
                        No product sales in this label.
                    </p>
                ) : isComparing ? (
                    <ChartContainer
                        config={comparisonConfig}
                        className="aspect-auto h-80 w-full"
                    >
                        <BarChart
                            accessibilityLayer
                            data={comparisonData}
                            margin={{ top: 24 }}
                        >
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="name"
                                tickLine={false}
                                tickMargin={10}
                                axisLine={false}
                                tickFormatter={(value: string) =>
                                    value.length > 14
                                        ? `${value.slice(0, 14)}…`
                                        : value
                                }
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent
                                        indicator="dashed"
                                        labelFormatter={(_label, payload) =>
                                            (
                                                payload?.[0]?.payload as
                                                    | { name?: string }
                                                    | undefined
                                            )?.name ?? "Product"
                                        }
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
                                                        {name === "current"
                                                            ? label
                                                            : compareLabel}
                                                    </span>
                                                </span>
                                                <span className="text-foreground font-medium tabular-nums">
                                                    {formatCount(
                                                        Number(value) || 0,
                                                    )}{" "}
                                                    units sold
                                                </span>
                                            </div>
                                        )}
                                    />
                                }
                            />
                            <ChartLegend content={<ChartLegendContent />} />
                            <Bar
                                dataKey="current"
                                fill="var(--color-current)"
                                shape={NonZeroBar}
                            >
                                <LabelList
                                    dataKey="current"
                                    position="top"
                                    offset={6}
                                    className="fill-foreground text-xs"
                                />
                            </Bar>
                            <Bar
                                dataKey="previous"
                                fill="var(--color-previous)"
                                shape={NonZeroBar}
                            >
                                <LabelList
                                    dataKey="previous"
                                    position="top"
                                    offset={6}
                                    className="fill-foreground text-xs"
                                />
                            </Bar>
                        </BarChart>
                    </ChartContainer>
                ) : (
                    <ChartContainer
                        config={productChartConfig}
                        className="aspect-auto h-70 w-full"
                    >
                        <BarChart
                            accessibilityLayer
                            data={products}
                            layout="vertical"
                            margin={{
                                left: 8,
                                right: 48,
                                top: 8,
                                bottom: 8,
                            }}
                        >
                            <CartesianGrid horizontal={false} />
                            <XAxis type="number" hide />
                            <YAxis
                                dataKey="name"
                                type="category"
                                tickLine={false}
                                axisLine={false}
                                width={120}
                                tickMargin={8}
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent
                                        hideLabel
                                        hideIndicator
                                        formatter={(value, _name, item) => (
                                            <ReportTooltipBody
                                                name={String(
                                                    (
                                                        item.payload as
                                                            | { name?: string }
                                                            | undefined
                                                    )?.name ?? "Product",
                                                )}
                                                period={label}
                                                label="Units sold"
                                                value={`${formatCount(Number(value) || 0)} units`}
                                            />
                                        )}
                                    />
                                }
                            />
                            <Bar
                                dataKey="sold"
                                fill="var(--color-sold)"
                                radius={4}
                            >
                                <LabelList
                                    dataKey="sold"
                                    position="right"
                                    offset={8}
                                    className="fill-foreground text-xs"
                                />
                            </Bar>
                        </BarChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}

function BranchesPie({ label, rows }: { label: string; rows: BranchRow[] }) {
    const branches = useMemo(
        () =>
            rows.map((branch) => ({
                ...branch,
                fill: `var(--color-${branch.branch})`,
            })),
        [rows],
    );

    const chartConfig = useMemo(
        () =>
            buildNamedChartConfig(
                branches.map((branch) => ({
                    key: branch.branch,
                    label: branch.name,
                })),
            ),
        [branches],
    );

    if (branches.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No branch sales in this label.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            <ChartContainer
                config={chartConfig}
                className="mx-auto aspect-square max-h-72"
            >
                <PieChart>
                    <ChartTooltip
                        content={
                            <ChartTooltipContent
                                nameKey="branch"
                                hideLabel
                                hideIndicator
                                formatter={(value, _name, item) => (
                                    <ReportTooltipBody
                                        name={
                                            (
                                                item.payload as
                                                    | { name?: string }
                                                    | undefined
                                            )?.name ?? "Branch"
                                        }
                                        period={label}
                                        label="Sales"
                                        value={formatMoney(Number(value) || 0)}
                                    />
                                )}
                            />
                        }
                    />
                    <Pie
                        data={branches}
                        dataKey="sales"
                        nameKey="branch"
                        innerRadius={55}
                        strokeWidth={2}
                    >
                        <LabelList
                            dataKey="sales"
                            className="fill-white text-xs"
                            stroke="none"
                            formatter={(value) =>
                                typeof value === "number"
                                    ? formatCompactMoney(value)
                                    : String(value ?? "")
                            }
                        />
                    </Pie>
                </PieChart>
            </ChartContainer>

            <div className="flex w-full flex-wrap justify-center gap-x-4 gap-y-2">
                {branches.map((branch) => (
                    <div
                        key={branch.branch}
                        className="flex min-w-0 items-center gap-1.5 text-sm"
                    >
                        <span
                            className="size-2.5 shrink-0 rounded-[2px]"
                            style={{
                                backgroundColor: chartColor(
                                    chartConfig,
                                    branch.branch,
                                ),
                            }}
                        />
                        <span className="truncate">{branch.name}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function DriversRadial({ label, rows }: { label: string; rows: DriverRow[] }) {
    const drivers = useMemo(
        () =>
            rows.map((driver) => ({
                ...driver,
                fill: `var(--color-${driver.driver})`,
            })),
        [rows],
    );

    const chartConfig = useMemo(
        () =>
            buildNamedChartConfig(
                drivers.map((driver) => ({
                    key: driver.driver,
                    label: driver.name,
                })),
            ),
        [drivers],
    );

    const deliveryMax = Math.max(
        1,
        ...drivers.map((driver) => driver.deliveries),
    );

    if (drivers.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No driver deliveries in this label.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            <ChartContainer
                config={chartConfig}
                className="mx-auto aspect-square max-h-72"
            >
                <RadialBarChart
                    data={drivers}
                    startAngle={90}
                    endAngle={-270}
                    innerRadius={24}
                    outerRadius={110}
                >
                    <PolarAngleAxis
                        type="number"
                        domain={[0, deliveryMax]}
                        tick={false}
                    />
                    <ChartTooltip
                        content={
                            <ChartTooltipContent
                                nameKey="driver"
                                hideLabel
                                hideIndicator
                                formatter={(value, _name, item) => (
                                    <ReportTooltipBody
                                        name={
                                            (
                                                item.payload as
                                                    | { name?: string }
                                                    | undefined
                                            )?.name ?? "Driver"
                                        }
                                        period={label}
                                        label="Deliveries"
                                        value={`${formatCount(Number(value) || 0)} deliveries`}
                                    />
                                )}
                            />
                        }
                    />
                    <RadialBar
                        dataKey="deliveries"
                        background
                        cornerRadius={4}
                    />
                </RadialBarChart>
            </ChartContainer>

            <div className="flex w-full flex-wrap justify-center gap-x-4 gap-y-2">
                {drivers.map((driver) => (
                    <div
                        key={driver.driver}
                        className="flex min-w-0 items-center gap-1.5 text-sm"
                    >
                        <span
                            className="size-2.5 shrink-0 rounded-[2px]"
                            style={{
                                backgroundColor: chartColor(
                                    chartConfig,
                                    driver.driver,
                                ),
                            }}
                        />
                        <span className="truncate">{driver.name}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

/**
 * Renders one chart for the selected label and, when comparing, the same
 * chart for the compare label next to it inside the same card.
 */
function MonthPair({
    label,
    compareLabel,
    children,
}: {
    label: string;
    compareLabel?: string;
    children: (label: string) => React.ReactNode;
}) {
    if (!compareLabel) {
        return <>{children(label)}</>;
    }

    return (
        <div className="grid gap-6 sm:grid-cols-2">
            {[label, compareLabel].map((item) => (
                <div key={item} className="space-y-3">
                    <p className="text-center text-sm font-medium">{item}</p>
                    {children(item)}
                </div>
            ))}
        </div>
    );
}

function BranchesChartCard({
    report,
    compare,
}: {
    report: MonthReport;
    compare: MonthReport | null;
}) {
    return (
        <Card className="shadow-none">
            <CardHeader>
                <div className="space-y-1">
                    <CardTitle>Which branches sell most</CardTitle>
                    <CardDescription>
                        {compare
                            ? `Branch sales in ${report.label} compared with ${compare.label}.`
                            : `Branches with the highest sales in ${report.label}.`}
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                <MonthPair label={report.label} compareLabel={compare?.label}>
                    {(item) => (
                        <BranchesPie
                            label={item}
                            rows={
                                item === report.label
                                    ? report.branches
                                    : (compare?.branches ?? [])
                            }
                        />
                    )}
                </MonthPair>
            </CardContent>
        </Card>
    );
}

function DriversChartCard({
    report,
    compare,
}: {
    report: MonthReport;
    compare: MonthReport | null;
}) {
    return (
        <Card className="shadow-none">
            <CardHeader>
                <div className="space-y-1">
                    <CardTitle>Which drivers deliver most</CardTitle>
                    <CardDescription>
                        {compare
                            ? `Deliveries in ${report.label} compared with ${compare.label}.`
                            : `Drivers who completed the most deliveries in ${report.label}.`}
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent>
                <MonthPair label={report.label} compareLabel={compare?.label}>
                    {(item) => (
                        <DriversRadial
                            label={item}
                            rows={
                                item === report.label
                                    ? report.drivers
                                    : (compare?.drivers ?? [])
                            }
                        />
                    )}
                </MonthPair>
            </CardContent>
        </Card>
    );
}

export default function PlatformOrderReports({
    dashboard,
}: {
    dashboard: Dashboard;
}) {
    const month = dashboard.month;
    const range = dashboard.range;
    const compare = dashboard.compare;
    const summary = dashboard.summary;

    const summaryCards: Array<{
        label: string;
        key: keyof Summary;
        money?: boolean;
        // Higher is worse (cancelled / refunded / failed): flips delta color.
        inverse?: boolean;
    }> = [
        { label: "Total orders", key: "orders" },
        {
            label: "Total payment collected",
            key: "payments_collected",
            money: true,
        },
        { label: "Cancelled orders", key: "cancelled", inverse: true },
        { label: "Refunded orders", key: "refunded", inverse: true },
        {
            label: "Refunded amount",
            key: "refunded_amount",
            money: true,
            inverse: true,
        },
        { label: "Delivered", key: "delivered" },
        { label: "Waiting to deliver", key: "waiting" },
        { label: "Could not deliver", key: "failed", inverse: true },
        { label: "Money from sales", key: "sales", money: true },
        {
            label: "Cash collected on delivery",
            key: "cod_collected",
            money: true,
        },
    ];

    const formatValue = (value: number, money?: boolean): string =>
        money ? formatMoney(value) : formatCount(value);

    const now = new Date();
    const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
    const isRangeMode = range !== null;

    const changeFilters = (next: {
        month: string | null;
        range: string | null;
        compare: string | null;
    }) => {
        const filter = {
            ...(next.range
                ? { range: next.range }
                : next.month
                  ? { month: next.month }
                  : {}),
            ...(next.compare ? { compare: next.compare } : {}),
        };

        router.get(
            OrderReportController.index.url(
                Object.keys(filter).length > 0 ? { query: { filter } } : {},
            ),
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const resetFilters = () =>
        changeFilters({ month: null, range: null, compare: null });

    return (
        <>
            <Head title="Order Reports" />

            <div className="space-y-6 p-2 md:p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Order Reports"
                        description="See how orders, products, branches, and drivers are doing."
                    />

                    <div className="flex w-full items-center gap-2 sm:w-auto">
                        <OrderReportFilters
                            month={month}
                            range={range}
                            compareMonth={compare?.month ?? null}
                            isComparing={compare !== null}
                            label={dashboard.label}
                            compareLabel={compare?.label ?? null}
                            onApply={changeFilters}
                            onReset={resetFilters}
                        />

                        <ExportDropdown
                            url={OrderReportController.export.url()}
                            query={{
                                filter: {
                                    ...(isRangeMode
                                        ? { range }
                                        : { month: month ?? currentMonth }),
                                    ...(compare
                                        ? {
                                              compare: isRangeMode
                                                  ? "previous"
                                                  : compare.month,
                                          }
                                        : {}),
                                },
                                sort: null,
                                per_page: 15,
                                page: 1,
                            }}
                            hasDateRange={false}
                        />
                    </div>
                </div>

                <section className="space-y-3">
                    <div>
                        <h2 className="text-lg font-semibold tracking-tight">
                            At a glance
                        </h2>
                        <p className="text-muted-foreground mt-0.5 text-sm">
                            {compare
                                ? `${dashboard.label} compared with ${compare.label}.`
                                : `The main numbers for ${dashboard.label}, compared with ${dashboard.trend.label}.`}
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {summaryCards.map((item) => {
                            const current = summary[item.key];
                            const previous = dashboard.trend.summary[item.key];
                            const diff = current - previous;
                            const isGood = item.inverse ? diff < 0 : diff > 0;

                            return (
                                <Card
                                    key={item.label}
                                    className="gap-3 py-4 shadow-none"
                                >
                                    <CardHeader className="px-4">
                                        <CardDescription>
                                            {item.label}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-1 px-4">
                                        <p className="text-2xl font-semibold tracking-tight tabular-nums">
                                            {formatValue(current, item.money)}
                                        </p>
                                        <p className="text-muted-foreground flex flex-wrap items-center gap-x-1.5 text-xs tabular-nums">
                                            <span
                                                className={cn(
                                                    "inline-flex items-center gap-0.5 font-medium",
                                                    diff === 0
                                                        ? "text-muted-foreground"
                                                        : isGood
                                                          ? "text-emerald-600"
                                                          : "text-red-600",
                                                )}
                                            >
                                                {diff > 0 ? (
                                                    <ArrowUp className="size-3.5" />
                                                ) : diff < 0 ? (
                                                    <ArrowDown className="size-3.5" />
                                                ) : (
                                                    <Minus className="size-3.5" />
                                                )}
                                                {changeLabel(current, previous)}
                                            </span>
                                            <span>
                                                vs {dashboard.trend.label}:{" "}
                                                {formatValue(
                                                    previous,
                                                    item.money,
                                                )}
                                            </span>
                                        </p>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </section>

                <OrderReportTimelineCard report={dashboard} compare={compare} />

                <ProductsChartCard
                    label={dashboard.label}
                    products={dashboard.products}
                    compareLabel={compare?.label}
                    comparison={compare?.product_comparison}
                />

                <div className="grid gap-4 lg:grid-cols-2">
                    <BranchesChartCard report={dashboard} compare={compare} />
                    <DriversChartCard report={dashboard} compare={compare} />
                </div>
            </div>
        </>
    );
}

PlatformOrderReports.layout = {
    breadcrumbs: [
        { title: "Dashboard", href: "/platform/dashboard" },
        { title: "Reports", href: "/platform/reports/order-reports" },
        {
            title: "Order Reports",
            href: "/platform/reports/order-reports",
        },
    ],
};
