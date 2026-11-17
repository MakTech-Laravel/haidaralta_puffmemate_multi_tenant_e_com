---
paths:
    - 'app/Http/Controllers/Platform/Reports/OrderReportController.php'
    - 'app/Services/Order/OrderReportService.php'
    - 'app/Exports/OrderReportsExport.php'
    - 'resources/js/pages/platform/reports/order-reports.tsx'
---

# Platform Order Reports: monthly summary and compare

> Build step: save this file as `.ai/rules/order-reports.md` and add a row to `.ai/rules/index.md`
> (globs above -> `.ai/rules/order-reports.md`).

## Context

Platform > Reports > Order Reports (`/platform/reports/order-reports`) today uses rolling periods
(7d / 30d / 90d / 1y) and shows: total orders, delivered, waiting, could-not-deliver, sales, COD collected,
plus top-5 products / branches / drivers. The new need is a month-based report:

- Total order
- Total payment collected
- Total cancelled order
- Total refunded order
- Top-selling 5 products
- Default = current month
- Filter any month
- Compare any two months

## Decisions (defaults, change only if the user objects)

- Replace the 7d/30d/90d/1y toggle with a **month picker** (`YYYY-MM`). Default = current month.
- **Compare** = a second optional month (`compare`). When set, each card shows the compare value, the difference and the % change.
- Keep the existing Delivered / Waiting / Failed / Sales cards and the top branches / drivers sections, but they now follow the selected month (less churn, no lost info).
- Branch report (`Branch/Reports/OrderReportController`) is NOT touched. Only the platform one changes.

## Metric definitions (all by `created_at` inside the month: start-of-month .. end-of-month)

| Metric | Source |
|---|---|
| Total orders | `Order` count |
| Total payment collected | `Payment` where status in (`collected`, `verified`), sum `amount` (all gateways, not just COD). Keep the existing `cod_collected` too. |
| Total cancelled orders | `Order` where `status = OrderStatus::Cancelled`, count |
| Total refunded orders | `Payment` where `status = PaymentStatus::Refunded`, count distinct `order_id` (+ refunded amount sum as secondary value) |
| Top 5 products | existing `topProducts()` on `OrderItem`, scoped to the month |

Note: `PaymentMarkingService::refund()` sets `Payment.status = Refunded` (refund is only allowed after arrived/delivered). `OrderReturn` (type refund/defect request) is a request, not the money refund, so it is not used here. Confirm with the user if "refund" should include approved `OrderReturn` rows.

## Backend changes

1. `app/Services/Order/OrderReportService.php`
   - Add `resolveMonth(?string $month): string` (validates `Y-m`, falls back to current month). Mirrors `resolvePeriod()`.
   - Add `monthRange(string $month): array{0: CarbonInterface, 1: CarbonInterface}` (startOfMonth/endOfMonth).
   - Change `platformDashboard(string $month, ?string $compare = null)` to return:
     `month`, `summary` (adds `payments_collected`, `cancelled`, `refunded`, `refunded_amount`), `products`, `branches`, `drivers`, and `compare` (same `summary` + `products` for the compare month, or `null`).
   - Extend `platformSummary()` with the 3 new metrics. Reuse `topProducts/topBranches/topDrivers` by passing the month range (they already take `$from, $to`).
   - Keep `periods()/resolvePeriod()/periodRange()` only if the branch report or tests still use them; otherwise remove. Update the PHPDoc array shapes.
2. `app/Http/Controllers/Platform/Reports/OrderReportController.php`
   - Read `filter.month` and `filter.compare` (strings) in `index()` and `export()`; pass through the service.
   - Add a small FormRequest or validation (`date_format:Y-m`, `compare` != `month`) if consistent with `ExportSpreadsheetRequest`.
3. `app/Exports/OrderReportsExport.php`
   - Replace `Period` row with `Month` (and `Compare month` when set); add the 3 new rows; when comparing, add columns `Compare` and `Change %`.

## Frontend changes (`resources/js/pages/platform/reports/order-reports.tsx`)

- Replace `PERIODS` + `ToggleGroup` with a month picker. Check `components/ui` for an existing month/date picker first (`date-picker.tsx`, `calendar.tsx`; fall back to `<input type="month">` styled like the other inputs). Add an optional "Compare with" month picker and a clear button.
- `changePeriod` -> `changeFilters({month, compare})`, `router.get(OrderReportController.index.url({query:{filter:{month, compare}}}), {}, {preserveState:true, replace:true})`.
- Update `Dashboard` type; add cards: Total payment collected, Cancelled orders, Refunded orders.
- When `dashboard.compare` exists, render under each card value: compare value, delta (green/red arrow) and % change. Optional: a grouped bar chart (this month vs compare) for the top-5 products using the existing recharts `BarChart`.
- Pass `filter: { month, compare }` to `ExportDropdown`.
- Use the existing Wayfinder `OrderReportController` action import; run `php artisan wayfinder:generate` only if the route signature changes (it should not).

## Tests (`tests/Feature/Platform/OrderReportTest.php`, Pest)

- Default (no filter) returns the current month and counts only orders created in it.
- Cancelled and refunded counts and payment collected sum are correct; orders outside the month are excluded.
- `filter[month]=2026-10` returns that month; invalid value falls back to current month.
- `filter[compare]` returns a `compare` block with correct values; same-month compare is ignored.
- Export CSV contains the new rows. Update the existing period-based assertions (lines ~136-230) to month-based.
- Run: `php artisan test --compact tests/Feature/Platform/OrderReportTest.php`, then `vendor/bin/pint --dirty --format agent`.

## Verification

1. Open `/platform/reports/order-reports`: current month selected, 4 new cards + top-5 products show.
2. Pick a past month: numbers change, URL has `filter[month]`.
3. Set "Compare with": deltas and % appear; clear it: they disappear.
4. Export CSV/XLSX: contains month, new metrics, compare columns.
5. `npm run build` (or `npm run dev`) and check there are no TS errors; `/platform/reports/order-reports` still guarded by `manage-reports`.

## Clearing filters

Clearing Month or Compare resets to the current month (no filter params are sent). Reset button shows when the view is not the default.
