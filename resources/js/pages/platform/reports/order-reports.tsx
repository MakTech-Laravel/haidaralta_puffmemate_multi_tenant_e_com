import { Head, router } from "@inertiajs/react";
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
    XAxis,
    YAxis,
} from "recharts";
import OrderReportController from "@/actions/App/Http/Controllers/Platform/Reports/OrderReportController";
import { ExportDropdown } from "@/components/export/export-dropdown";
import Heading from "@/components/heading";
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
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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

type Dashboard = {
    month: string;
    summary: Summary;
    products: ProductRow[];
    branches: BranchRow[];
    drivers: DriverRow[];
    compare: {
        month: string;
        summary: Summary;
        products: ProductRow[];
    } | null;
};

function formatMonth(value: string): string {
    const [year, month] = value.split("-").map(Number);

    return new Date(year, month - 1, 1).toLocaleDateString(undefined, {
        month: "long",
        year: "numeric",
    });
}

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

export default function PlatformOrderReports({
    dashboard,
}: {
    dashboard: Dashboard;
}) {
    const month = dashboard.month;
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

    const compareProducts = useMemo(() => {
        if (!compare) {
            return [];
        }

        const names = Array.from(
            new Set([
                ...dashboard.products.map((row) => row.name),
                ...compare.products.map((row) => row.name),
            ]),
        );

        return names.map((name) => ({
            name,
            current:
                dashboard.products.find((row) => row.name === name)?.sold ?? 0,
            previous:
                compare.products.find((row) => row.name === name)?.sold ?? 0,
        }));
    }, [dashboard.products, compare]);

    const branches = useMemo(
        () =>
            dashboard.branches.map((branch, index) => ({
                ...branch,
                fill: `var(--color-${branch.branch})`,
                color: CHART_COLORS[index % CHART_COLORS.length],
            })),
        [dashboard.branches],
    );

    const drivers = useMemo(
        () =>
            dashboard.drivers.map((driver, index) => ({
                ...driver,
                fill: `var(--color-${driver.driver})`,
                color: CHART_COLORS[index % CHART_COLORS.length],
            })),
        [dashboard.drivers],
    );

    const branchChartConfig = useMemo(
        () =>
            buildNamedChartConfig(
                branches.map((branch) => ({
                    key: branch.branch,
                    label: branch.name,
                })),
            ),
        [branches],
    );

    const driverChartConfig = useMemo(
        () =>
            buildNamedChartConfig(
                drivers.map((driver) => ({
                    key: driver.driver,
                    label: driver.name,
                })),
            ),
        [drivers],
    );

    const driverDeliveryMax = Math.max(
        1,
        ...drivers.map((driver) => driver.deliveries),
    );

    const now = new Date();
    const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
    const isDefaultView = month === currentMonth && !compare;

    const changeFilters = (next: {
        month: string | null;
        compare: string | null;
    }) => {
        const filter = {
            ...(next.month ? { month: next.month } : {}),
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

    return (
        <>
            <Head title="Order Reports" />

            <div className="space-y-6 p-2 md:p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <Heading
                        title="Order Reports"
                        description="See how orders, products, branches, and drivers are doing."
                    />

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end">
                        <div className="space-y-1.5">
                            <Label htmlFor="report-month">Month</Label>
                            <Input
                                key={`month-${month}`}
                                id="report-month"
                                type="month"
                                defaultValue={month}
                                onChange={(event) =>
                                    changeFilters({
                                        month: event.target.value || null,
                                        compare: compare?.month ?? null,
                                    })
                                }
                                className="h-9 w-44"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="report-compare">Compare with</Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    key={`compare-${compare?.month ?? ""}`}
                                    id="report-compare"
                                    type="month"
                                    defaultValue={compare?.month ?? ""}
                                    onChange={(event) =>
                                        event.target.value
                                            ? changeFilters({
                                                  month,
                                                  compare: event.target.value,
                                              })
                                            : changeFilters({
                                                  month: null,
                                                  compare: null,
                                              })
                                    }
                                    className="h-9 w-44"
                                />
                                {compare ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            changeFilters({
                                                month: null,
                                                compare: null,
                                            })
                                        }
                                    >
                                        Clear
                                    </Button>
                                ) : null}
                                {!isDefaultView && !compare ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            changeFilters({
                                                month: null,
                                                compare: null,
                                            })
                                        }
                                    >
                                        Reset
                                    </Button>
                                ) : null}
                            </div>
                        </div>

                        <ExportDropdown
                            url={OrderReportController.export.url()}
                            query={{
                                filter: {
                                    month,
                                    ...(compare
                                        ? { compare: compare.month }
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
                                ? `${formatMonth(month)} compared with ${formatMonth(compare.month)}.`
                                : `The main numbers for ${formatMonth(month)}.`}
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {summaryCards.map((item) => {
                            const current = summary[item.key];
                            const previous = compare?.summary[item.key] ?? 0;
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
                                        {compare ? (
                                            <p className="text-muted-foreground text-xs tabular-nums">
                                                {formatMonth(compare.month)}:{" "}
                                                {formatValue(
                                                    previous,
                                                    item.money,
                                                )}
                                                {" · "}
                                                <span
                                                    className={cn(
                                                        "font-medium",
                                                        diff === 0
                                                            ? ""
                                                            : isGood
                                                              ? "text-emerald-600"
                                                              : "text-red-600",
                                                    )}
                                                >
                                                    {changeLabel(
                                                        current,
                                                        previous,
                                                    )}
                                                </span>
                                            </p>
                                        ) : null}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </section>

                <Card className="shadow-none">
                    <CardHeader>
                        <div className="space-y-1">
                            <CardTitle>Top 5 selling products</CardTitle>
                            <CardDescription>
                                Products customers bought the most in{" "}
                                {formatMonth(month)}.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {compare ? (
                            <ChartContainer
                                config={{
                                    current: {
                                        label: formatMonth(month),
                                        color: "var(--chart-1)",
                                    },
                                    previous: {
                                        label: formatMonth(compare.month),
                                        color: "var(--chart-2)",
                                    },
                                }}
                                className="aspect-auto h-80 w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={compareProducts}
                                    margin={{ top: 16, bottom: 8 }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="name"
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                    />
                                    <YAxis hide />
                                    <ChartTooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    <ChartLegend
                                        content={<ChartLegendContent />}
                                    />
                                    <Bar
                                        dataKey="current"
                                        fill="var(--color-current)"
                                        radius={4}
                                    />
                                    <Bar
                                        dataKey="previous"
                                        fill="var(--color-previous)"
                                        radius={4}
                                    />
                                </BarChart>
                            </ChartContainer>
                        ) : dashboard.products.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No product sales in this month.
                            </p>
                        ) : (
                            <ChartContainer
                                config={productChartConfig}
                                className="aspect-auto h-70 w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={dashboard.products}
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
                                            <ChartTooltipContent hideLabel />
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

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="shadow-none">
                        <CardHeader>
                            <div className="space-y-1">
                                <CardTitle>Which branches sell most</CardTitle>
                                <CardDescription>
                                    Branches with the highest sales.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {branches.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No branch sales in this month.
                                </p>
                            ) : (
                                <>
                                    <ChartContainer
                                        config={branchChartConfig}
                                        className="mx-auto aspect-square max-h-72"
                                    >
                                        <PieChart>
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent
                                                        nameKey="branch"
                                                        formatter={(
                                                            value,
                                                            _name,
                                                            item,
                                                        ) => {
                                                            const payload =
                                                                item.payload as
                                                                    | {
                                                                          branch?: string;
                                                                          name?: string;
                                                                      }
                                                                    | undefined;
                                                            const branchName =
                                                                payload?.name ??
                                                                "Branch";

                                                            return (
                                                                <div className="flex w-full items-center justify-between gap-4">
                                                                    <span className="text-muted-foreground">
                                                                        {
                                                                            branchName
                                                                        }
                                                                    </span>
                                                                    <span className="text-foreground font-medium tabular-nums">
                                                                        {typeof value ===
                                                                        "number"
                                                                            ? formatMoney(
                                                                                  value,
                                                                              )
                                                                            : formatMoney(
                                                                                  Number(
                                                                                      value,
                                                                                  ) ||
                                                                                      0,
                                                                              )}
                                                                    </span>
                                                                </div>
                                                            );
                                                        }}
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
                                                        typeof value ===
                                                        "number"
                                                            ? formatCompactMoney(
                                                                  value,
                                                              )
                                                            : String(
                                                                  value ?? "",
                                                              )
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
                                                        backgroundColor:
                                                            chartColor(
                                                                branchChartConfig,
                                                                branch.branch,
                                                            ),
                                                    }}
                                                />
                                                <span className="truncate">
                                                    {branch.name}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="shadow-none">
                        <CardHeader>
                            <div className="space-y-1">
                                <CardTitle>
                                    Which drivers deliver most
                                </CardTitle>
                                <CardDescription>
                                    Drivers who completed the most deliveries.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {drivers.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No driver deliveries in this month.
                                </p>
                            ) : (
                                <>
                                    <ChartContainer
                                        config={driverChartConfig}
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
                                                domain={[0, driverDeliveryMax]}
                                                tick={false}
                                            />
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent
                                                        nameKey="driver"
                                                        hideLabel
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
                                                        backgroundColor:
                                                            chartColor(
                                                                driverChartConfig,
                                                                driver.driver,
                                                            ),
                                                    }}
                                                />
                                                <span className="truncate">
                                                    {driver.name}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
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
