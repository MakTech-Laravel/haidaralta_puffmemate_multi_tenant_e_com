import { Head, router } from '@inertiajs/react';
import { useMemo } from 'react';
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
} from 'recharts';
import OrderReportController from '@/actions/App/Http/Controllers/Platform/Reports/OrderReportController';
import { ExportDropdown } from '@/components/export/export-dropdown';
import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    type ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';

type Period = '7d' | '30d' | '90d' | '1y';

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

type Dashboard = {
    period: Period;
    summary: {
        orders: number;
        delivered: number;
        waiting: number;
        failed: number;
        sales: number;
        cod_collected: number;
    };
    products: ProductRow[];
    branches: BranchRow[];
    drivers: DriverRow[];
};

const PERIODS: Array<{ value: Period; label: string }> = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: '1y', label: 'Last 1 year' },
];

const CHART_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
] as const;

const productChartConfig = {
    sold: {
        label: 'Units sold',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

function formatMoney(value: number): string {
    return `$${value.toLocaleString()}`;
}

function formatCompactMoney(value: number): string {
    const abs = Math.abs(value);
    const sign = value < 0 ? '-' : '';

    const withSuffix = (scaled: number, suffix: string): string => {
        const rounded = Math.round(scaled * 10) / 10;
        const text = Number.isInteger(rounded)
            ? String(rounded)
            : rounded.toFixed(1).replace(/\.0$/, '');

        return `${sign}$${text}${suffix}`;
    };

    if (abs >= 1_000_000_000) {
        return withSuffix(abs / 1_000_000_000, 'B');
    }

    if (abs >= 1_000_000) {
        return withSuffix(abs / 1_000_000, 'm');
    }

    if (abs >= 1_000) {
        return withSuffix(abs / 1_000, 'k');
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
    fallback = 'var(--chart-1)',
): string {
    const entry = config[key];

    return entry && 'color' in entry && entry.color ? entry.color : fallback;
}

export default function PlatformOrderReports({
    dashboard,
}: {
    dashboard: Dashboard;
}) {
    const period = dashboard.period;
    const summaryCards = [
        {
            label: 'Total orders',
            value: formatCount(dashboard.summary.orders),
        },
        {
            label: 'Delivered',
            value: formatCount(dashboard.summary.delivered),
        },
        {
            label: 'Waiting to deliver',
            value: formatCount(dashboard.summary.waiting),
        },
        {
            label: 'Could not deliver',
            value: formatCount(dashboard.summary.failed),
        },
        {
            label: 'Money from sales',
            value: formatMoney(dashboard.summary.sales),
        },
        {
            label: 'Cash collected on delivery',
            value: formatMoney(dashboard.summary.cod_collected),
        },
    ];

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

    const changePeriod = (next: Period) => {
        if (next === period) {
            return;
        }

        router.get(
            OrderReportController.index.url({
                query: {
                    filter: {
                        period: next,
                    },
                },
            }),
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
                            <p className="text-sm font-medium">
                                Show results for
                            </p>
                            <ToggleGroup
                                type="single"
                                value={period}
                                onValueChange={(value) => {
                                    if (
                                        value === '7d' ||
                                        value === '30d' ||
                                        value === '90d' ||
                                        value === '1y'
                                    ) {
                                        changePeriod(value);
                                    }
                                }}
                                variant="outline"
                                size="sm"
                                className="flex flex-wrap justify-start gap-2"
                            >
                                {PERIODS.map((item) => (
                                    <ToggleGroupItem
                                        key={item.value}
                                        value={item.value}
                                        aria-label={item.label}
                                        className={cn(
                                            'h-9 min-w-28 flex-none rounded-md border px-3 first:rounded-md last:rounded-md data-[variant=outline]:border-l',
                                            'data-[state=on]:bg-primary data-[state=on]:text-primary-foreground data-[state=on]:hover:bg-primary/90',
                                        )}
                                    >
                                        {item.label}
                                    </ToggleGroupItem>
                                ))}
                            </ToggleGroup>
                        </div>

                        <ExportDropdown
                            url={OrderReportController.export.url()}
                            query={{
                                filter: {
                                    period,
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
                            The main numbers for the time you selected.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {summaryCards.map((item) => (
                            <Card
                                key={item.label}
                                className="gap-3 py-4 shadow-none"
                            >
                                <CardHeader className="px-4">
                                    <CardDescription>
                                        {item.label}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="px-4">
                                    <p className="text-2xl font-semibold tracking-tight tabular-nums">
                                        {item.value}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </section>

                <Card className="shadow-none">
                    <CardHeader>
                        <div className="space-y-1">
                            <CardTitle>What sells most</CardTitle>
                            <CardDescription>
                                Products customers bought the most in this time.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {dashboard.products.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No product sales in this period.
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
                                    No branch sales in this period.
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
                                                                'Branch';

                                                            return (
                                                                <div className="flex w-full items-center justify-between gap-4">
                                                                    <span className="text-muted-foreground">
                                                                        {
                                                                            branchName
                                                                        }
                                                                    </span>
                                                                    <span className="text-foreground font-medium tabular-nums">
                                                                        {typeof value ===
                                                                        'number'
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
                                                        'number'
                                                            ? formatCompactMoney(
                                                                  value,
                                                              )
                                                            : String(
                                                                  value ?? '',
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
                                    No driver deliveries in this period.
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
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Reports', href: '/platform/reports/order-reports' },
        {
            title: 'Order Reports',
            href: '/platform/reports/order-reports',
        },
    ],
};
