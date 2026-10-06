import { Head } from '@inertiajs/react';
import { useState } from 'react';
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
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
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

type DriverReportRow = {
    id: number;
    name: string;
    delivered: number;
    failedAttempts: number;
    collected: number;
    collectedCod: number;
    collectedPos: number;
};

type PlatformOrderOperationsProps = {
    report: {
        orders: number;
        delivered: number;
        sales: number;
        transferCost: number;
        payments: Record<string, number>;
    };
    drivers: DriverReportRow[];
};

const PERIODS: Array<{ value: Period; label: string }> = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: '1y', label: 'Last 1 year' },
];

const DEMO_SUMMARY: Record<
    Period,
    Array<{ label: string; value: string }>
> = {
    '7d': [
        { label: 'Total orders', value: '312' },
        { label: 'Delivered', value: '268' },
        { label: 'Waiting to deliver', value: '28' },
        { label: 'Could not deliver', value: '9' },
        { label: 'Money from sales', value: '$21,450' },
        { label: 'Cash collected on delivery', value: '$3,180' },
    ],
    '30d': [
        { label: 'Total orders', value: '1,284' },
        { label: 'Delivered', value: '1,102' },
        { label: 'Waiting to deliver', value: '96' },
        { label: 'Could not deliver', value: '41' },
        { label: 'Money from sales', value: '$86,420' },
        { label: 'Cash collected on delivery', value: '$12,340' },
    ],
    '90d': [
        { label: 'Total orders', value: '3,640' },
        { label: 'Delivered', value: '3,210' },
        { label: 'Waiting to deliver', value: '214' },
        { label: 'Could not deliver', value: '98' },
        { label: 'Money from sales', value: '$241,800' },
        { label: 'Cash collected on delivery', value: '$34,920' },
    ],
    '1y': [
        { label: 'Total orders', value: '14,820' },
        { label: 'Delivered', value: '13,105' },
        { label: 'Waiting to deliver', value: '420' },
        { label: 'Could not deliver', value: '386' },
        { label: 'Money from sales', value: '$968,200' },
        { label: 'Cash collected on delivery', value: '$142,600' },
    ],
};

const DEMO_PRODUCTS = [
    { name: 'Mint Ice', sold: 420 },
    { name: 'Blue Razz', sold: 365 },
    { name: 'Grape Chill', sold: 298 },
    { name: 'Mango Mix', sold: 241 },
    { name: 'Classic Tobacco', sold: 188 },
];

const DEMO_BRANCHES = [
    {
        branch: 'sydney',
        name: 'Sydney CBD',
        sales: 1_500_000,
        fill: 'var(--color-sydney)',
    },
    {
        branch: 'melbourne',
        name: 'Melbourne',
        sales: 1_200_000,
        fill: 'var(--color-melbourne)',
    },
    {
        branch: 'brisbane',
        name: 'Brisbane',
        sales: 30_400,
        fill: 'var(--color-brisbane)',
    },
    {
        branch: 'perth',
        name: 'Perth',
        sales: 1_500000,
        fill: 'var(--color-perth)',
    },
    {
        branch: 'adelaide',
        name: 'Adelaide',
        sales: 1_20000,
        fill: 'var(--color-adelaide)',
    },
];

const DEMO_DRIVERS = [
    { driver: 'sam', name: 'Sam Lee', deliveries: 186, fill: 'var(--color-sam)' },
    { driver: 'jordan', name: 'Jordan Pak', deliveries: 164, fill: 'var(--color-jordan)' },
    { driver: 'riley', name: 'Riley Chen', deliveries: 141, fill: 'var(--color-riley)' },
    { driver: 'alex', name: 'Alex Kim', deliveries: 128, fill: 'var(--color-alex)' },
    { driver: 'casey', name: 'Casey Ng', deliveries: 97, fill: 'var(--color-casey)' },
];

const productChartConfig = {
    sold: {
        label: 'Units sold',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

const branchChartConfig = {
    sales: {
        label: 'Sales',
    },
    sydney: {
        label: 'Sydney CBD',
        color: 'var(--chart-1)',
    },
    melbourne: {
        label: 'Melbourne',
        color: 'var(--chart-2)',
    },
    brisbane: {
        label: 'Brisbane',
        color: 'var(--chart-3)',
    },
    perth: {
        label: 'Perth',
        color: 'var(--chart-4)',
    },
    adelaide: {
        label: 'Adelaide',
        color: 'var(--chart-5)',
    },
} satisfies ChartConfig;

const driverChartConfig = {
    deliveries: {
        label: 'Deliveries',
    },
    sam: {
        label: 'Sam Lee',
        color: 'var(--chart-1)',
    },
    jordan: {
        label: 'Jordan Pak',
        color: 'var(--chart-2)',
    },
    riley: {
        label: 'Riley Chen',
        color: 'var(--chart-3)',
    },
    alex: {
        label: 'Alex Kim',
        color: 'var(--chart-4)',
    },
    casey: {
        label: 'Casey Ng',
        color: 'var(--chart-5)',
    },
} satisfies ChartConfig;

const DRIVER_DELIVERY_MAX = Math.max(
    ...DEMO_DRIVERS.map((driver) => driver.deliveries),
);

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

export default function PlatformOrderOperations({
    report: _report,
    drivers: _drivers,
}: PlatformOrderOperationsProps) {
    const [period, setPeriod] = useState<Period>('30d');
    const summary = DEMO_SUMMARY[period];

    return (
        <>
            <Head title="Order Records" />

            <div className="space-y-6 p-2 md:p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <Heading
                        title="Order Records"
                        description="See how orders, products, branches, and drivers are doing."
                    />

                    <div className="space-y-1.5">
                        <p className="text-sm font-medium">Show results for</p>
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
                                    setPeriod(value);
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
                        {summary.map((item) => (
                            <Card
                                key={item.label}
                                className="gap-3 py-4 shadow-none"
                            >
                                <CardHeader className="px-4">
                                    <CardDescription>{item.label}</CardDescription>
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
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle>What sells most</CardTitle>
                            <CardDescription>
                                Products customers bought the most in this time.
                            </CardDescription>
                        </div>
                        <Button type="button" variant="outline">
                            Download
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            config={productChartConfig}
                            className="aspect-auto h-70 w-full"
                        >
                            <BarChart
                                accessibilityLayer
                                data={DEMO_PRODUCTS}
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
                                    content={<ChartTooltipContent hideLabel />}
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
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="shadow-none">
                        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <CardTitle>Which branches sell most</CardTitle>
                                <CardDescription>
                                    Branches with the highest sales.
                                </CardDescription>
                            </div>
                            {/* <Button type="button" variant="outline">
                                Download
                            </Button> */}
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <ChartContainer
                                config={branchChartConfig}
                                className="mx-auto aspect-square max-h-72"
                            >
                                <PieChart>
                                    <ChartTooltip
                                        content={
                                            <ChartTooltipContent
                                                nameKey="branch"
                                                formatter={(value, _name, item) => {
                                                    const payload = item.payload as
                                                        | {
                                                              branch?: string;
                                                              name?: string;
                                                          }
                                                        | undefined;
                                                    const branchKey = payload?.branch;
                                                    const configEntry =
                                                        branchKey != null
                                                            ? branchChartConfig[
                                                                  branchKey as keyof typeof branchChartConfig
                                                              ]
                                                            : undefined;
                                                    const branchName =
                                                        (configEntry &&
                                                            'label' in configEntry &&
                                                            typeof configEntry.label ===
                                                                'string'
                                                            ? configEntry.label
                                                            : null) ??
                                                        payload?.name ??
                                                        'Branch';

                                                    return (
                                                        <div className="flex w-full items-center justify-between gap-4">
                                                            <span className="text-muted-foreground">
                                                                {branchName}
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
                                        data={DEMO_BRANCHES}
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
                                                typeof value === 'number'
                                                    ? formatCompactMoney(value)
                                                    : String(value ?? '')
                                            }
                                        />
                                    </Pie>
                                </PieChart>
                            </ChartContainer>

                            <div className="flex w-full flex-wrap justify-center gap-x-4 gap-y-2">
                                {DEMO_BRANCHES.map((branch) => {
                                    const entry =
                                        branchChartConfig[
                                            branch.branch as keyof typeof branchChartConfig
                                        ];
                                    const color =
                                        entry && 'color' in entry
                                            ? entry.color
                                            : 'var(--chart-1)';

                                    return (
                                        <div
                                            key={branch.branch}
                                            className="flex min-w-0 items-center gap-1.5 text-sm"
                                        >
                                            <span
                                                className="size-2.5 shrink-0 rounded-[2px]"
                                                style={{
                                                    backgroundColor: color,
                                                }}
                                            />
                                            <span className="truncate">
                                                {branch.name}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-none">
                        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <CardTitle>
                                    Which drivers deliver most
                                </CardTitle>
                                <CardDescription>
                                    Drivers who completed the most deliveries.
                                </CardDescription>
                            </div>
                            {/* <Button type="button" variant="outline">
                                Download
                            </Button> */}
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <ChartContainer
                                config={driverChartConfig}
                                className="mx-auto aspect-square max-h-72"
                            >
                                <RadialBarChart
                                    data={DEMO_DRIVERS}
                                    startAngle={90}
                                    endAngle={-270}
                                    innerRadius={24}
                                    outerRadius={110}
                                >
                                    <PolarAngleAxis
                                        type="number"
                                        domain={[0, DRIVER_DELIVERY_MAX]}
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

                            <div className="flex w-full flex-wrap gap-x-4 justify-center gap-y-2">
                                {DEMO_DRIVERS.map((driver) => {
                                    const entry =
                                        driverChartConfig[
                                            driver.driver as keyof typeof driverChartConfig
                                        ];
                                    const color =
                                        entry && 'color' in entry
                                            ? entry.color
                                            : 'var(--chart-1)';

                                    return (
                                        <div
                                            key={driver.driver}
                                            className="flex min-w-0 items-center gap-1.5 text-sm"
                                        >
                                            <span
                                                className="size-2.5 shrink-0 rounded-[2px]"
                                                style={{
                                                    backgroundColor: color,
                                                }}
                                            />
                                            <span className="truncate">
                                                {driver.name}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

PlatformOrderOperations.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Reports', href: '/platform/reports/order-operations' },
        {
            title: 'Order Records',
            href: '/platform/reports/order-operations',
        },
    ],
};
