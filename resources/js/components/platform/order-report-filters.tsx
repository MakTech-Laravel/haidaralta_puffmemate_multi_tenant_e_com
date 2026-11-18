import { CalendarRange, ChevronDown, RotateCcw } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";

export type OrderReportFilterValues = {
    month: string | null;
    range: string | null;
    compare: string | null;
};

const RANGES: Array<{ value: string; label: string; previous: string }> = [
    { value: "7d", label: "Last 7 days", previous: "previous 7 days" },
    { value: "30d", label: "Last 30 days", previous: "previous 30 days" },
    { value: "90d", label: "Last 90 days", previous: "previous 90 days" },
    { value: "1y", label: "Last 1 year", previous: "previous year" },
];

function currentMonthValue(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
}

function previousMonthValue(month: string): string {
    const [year, value] = month.split("-").map(Number);

    if (!year || !value) {
        return "";
    }

    const date = new Date(year, value - 2, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
}

/**
 * One compact period filter: quick range, month and compare are drafted
 * together and applied with a single request.
 */
export function OrderReportFilters({
    month,
    range,
    compareMonth,
    isComparing,
    label,
    compareLabel,
    onApply,
    onReset,
}: {
    month: string | null;
    range: string | null;
    compareMonth: string | null;
    isComparing: boolean;
    label: string;
    compareLabel: string | null;
    onApply: (values: OrderReportFilterValues) => void;
    onReset: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [draftRange, setDraftRange] = useState<string | null>(range);
    const [draftMonth, setDraftMonth] = useState<string>(
        month ?? currentMonthValue(),
    );
    const [draftCompare, setDraftCompare] = useState<boolean>(isComparing);
    const [draftCompareMonth, setDraftCompareMonth] = useState<string>(
        compareMonth ?? previousMonthValue(month ?? currentMonthValue()),
    );

    const syncDraft = () => {
        const baseMonth = month ?? currentMonthValue();

        setDraftRange(range);
        setDraftMonth(baseMonth);
        setDraftCompare(isComparing);
        setDraftCompareMonth(compareMonth ?? previousMonthValue(baseMonth));
    };

    const isRangeMode = draftRange !== null;
    const activeRange = RANGES.find((item) => item.value === draftRange);
    const compareInvalid =
        draftCompare &&
        !isRangeMode &&
        (draftMonth === "" ||
            draftCompareMonth === "" ||
            draftCompareMonth === draftMonth);
    const canApply = isRangeMode || (draftMonth !== "" && !compareInvalid);

    const apply = () => {
        onApply({
            month: isRangeMode ? null : draftMonth,
            range: draftRange,
            compare: draftCompare
                ? isRangeMode
                    ? "previous"
                    : draftCompareMonth
                : null,
        });
        setOpen(false);
    };

    const reset = () => {
        onReset();
        setOpen(false);
    };

    return (
        <Popover
            open={open}
            onOpenChange={(next) => {
                if (next) {
                    syncDraft();
                }

                setOpen(next);
            }}
        >
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 min-w-0 flex-1 justify-start gap-2 sm:flex-none"
                >
                    <CalendarRange className="text-muted-foreground size-4 shrink-0" />
                    <span className="truncate font-medium">{label}</span>
                    {compareLabel ? (
                        <span className="text-muted-foreground truncate font-normal">
                            vs {compareLabel}
                        </span>
                    ) : null}
                    <ChevronDown className="text-muted-foreground ml-auto size-4 shrink-0" />
                </Button>
            </PopoverTrigger>

            <PopoverContent
                align="end"
                className="w-[min(24rem,calc(100vw-2rem))] space-y-4"
            >
                <div className="space-y-2">
                    <Label>Quick range</Label>
                    <div className="grid grid-cols-2 gap-2">
                        {RANGES.map((item) => (
                            <Button
                                key={item.value}
                                type="button"
                                size="sm"
                                variant={
                                    draftRange === item.value
                                        ? "default"
                                        : "outline"
                                }
                                aria-pressed={draftRange === item.value}
                                onClick={() => setDraftRange(item.value)}
                            >
                                {item.label}
                            </Button>
                        ))}
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="report-filter-month">Specific month</Label>
                    <Input
                        id="report-filter-month"
                        type="month"
                        value={isRangeMode ? "" : draftMonth}
                        onChange={(event) => {
                            setDraftRange(null);
                            setDraftMonth(event.target.value);
                        }}
                        className="h-9"
                    />
                </div>

                <Separator />

                <div className="space-y-3">
                    <div className="flex items-center justify-between gap-3">
                        <div className="space-y-0.5">
                            <Label htmlFor="report-filter-compare">
                                Compare
                            </Label>
                            <p className="text-muted-foreground text-xs">
                                {isRangeMode
                                    ? `Against the ${activeRange?.previous ?? "previous period"}`
                                    : "Against another month"}
                            </p>
                        </div>
                        <Switch
                            id="report-filter-compare"
                            checked={draftCompare}
                            onCheckedChange={setDraftCompare}
                        />
                    </div>

                    {draftCompare && !isRangeMode ? (
                        <div className="space-y-1.5">
                            <Label htmlFor="report-filter-compare-month">
                                Compare month
                            </Label>
                            <Input
                                id="report-filter-compare-month"
                                type="month"
                                value={draftCompareMonth}
                                onChange={(event) =>
                                    setDraftCompareMonth(event.target.value)
                                }
                                className="h-9"
                            />
                            {compareInvalid ? (
                                <p className="text-xs text-red-600">
                                    Pick a month different from the selected
                                    one.
                                </p>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <Separator />

                <div className="flex items-center justify-between gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={reset}
                    >
                        <RotateCcw className="size-3.5" />
                        Reset
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        disabled={!canApply}
                        onClick={apply}
                    >
                        Apply
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
