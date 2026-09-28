---
paths:
  - 'resources/js/components/branch/**'
  - resources/js/components/branch/nav.ts
  - resources/js/components/branch/product-inventory-panel.tsx
  - resources/js/components/branch/branch-product-summary.tsx
  - resources/js/components/branch/branch-pack-form.tsx
---

# Components Branch

## Branch inventory panel scopes by variant

Stock Manage uses ProductInventoryPanel: base + variant scope selector, posts existing inventory/price routes with variant_id. Never edit global catalog metadata from branch. UI uses Card sections + shared `DatePicker` (`@/components/ui/date-picker`, Calendar popover, `yyyy-MM-dd`) — prefer over native `input type="date"`.

## Branch Product management nav children

Inertia catalog pages live under `resources/js/pages/branch/catalog/{categories,products}` (same shape as platform). Categories routes have no destroy/restore. Product management always lists Products and Manage stock; Bulk Import only when `catalog.show_bulk_import`. When catalog.branch_can_view_categories is false, omit Categories. When true, keep Categories first. Keep the Categories and Bulk Import items in source; wrap them, do not delete.

## Inventory panel is a compact listing card

Inventory manage is one `shadow-sm` card: snapshot strip (Stock, Buying, Selling, Effective), then Stock left / Price right (`lg:grid-cols-2`) with Save in each section header. Price fields are Buying, Regular, Selling side by side (`sm:grid-cols-3`). Discount is a full-width bordered block below both sections (not inside the Price column). New entry requires Buying (prefilled from last buying_price); Reduce/Adjustment hide it. Do not float a StatCard, stretch equal-height footers, or leave an empty inventory column. DatePickers stay `min-w-0`. Stock type and discount type are 3-button rows, not full-width selects.

## Discount types + View transactions

Pricing uses discount_type none|fixed|percent. Label `fixed` as Amount off. Discounts apply only to selling. `fixed` effective is selling minus discount value (not the discount value itself). Honor date windows in the effective preview. No price, or `discount_type` none/missing, selects None and hides value+dates. Saved fixed/percent offers hydrate so Save is not destructive. Discount value label is Discount value (AUD) or (%), placeholders `e.g. 5.00` / `e.g. 10`. Helper for fixed is Amount off selling, ≤ selling. Do not render the stock txn ledger or price history table on Manage. **View transactions** and **View price history** (outline) go to `reports/stock-transactions` and `reports/price-history` with the same `product` + `branch` query (no variant). Pass `transactionsUrl` and `priceHistoryUrl` into the panel.

## Saved selling price is always live

Do not show Price active. UpsertBranchProductPriceRequest has no `status`; BranchPricingService::upsert always persists `status => true`. Keep the DB column; list queries still use `where('status', true)`.

## Listing details live on product show

Stock Manage (after a pair is resolved) links to catalog product show with **View details**. Do not inline Listing details on Manage. Platform show is `platform.products.show`; branch show is `branch.products.show` (no Branch network table). Platform category chips link to category show; branch chips link only when `catalog.branch_can_view_categories`.

## Inventory panel is quantity-only Stock

Inventory snapshot is { quantity } only (Stock). Do not post minimum_stock, reserved, available, or low-stock fields. Hide variant scope UI when catalog.show_product_variants is false. Keep adjust + price forms.

## Summary is listing details, not stock

Branch product summary is catalog listing details only. Stock and price live in ProductInventoryPanel. No enable toggle, low-stock badge, or available quantity.

## Inventory panel Adjustment is counted on-hand

Product inventory panel quantity is a clearable digit string, not Number(). Adjustment uses counted on-hand plus a required reason and the same live variance preview as stock import. Hide reason for New entry / Reduce. Disable Save when counted matches current stock.

## Inventory panel reason sits beside quantity

Adjustment reason select sits beside counted quantity. Other shows a required describe-the-reason input. Hide reason for New entry / Reduce.

## Inventory panel keeps Stock and Price side by side

Inventory & pricing is one shadow-sm card: 4-stat strip, then Stock left / Price right. That row is one shared `border-b` with `lg:divide-x` (and `divide-y` when stacked). Do not put a separate `border-b` on each column and do not use `items-start` on that row — those make the bottom lines sit at different heights. Forms use `content-start` so Save stays at the top; do not stretch equal-height footers. Buying/Regular/Selling sit side by side in the Price column. Discount is a full-width bordered block below that row, with a 3-button type row (None/Amount off/Percent). Value + dates use a 3-col row with DatePickers min-w-0. Do not put discount_type in the price-field grid, and do not nest discount inside the Price column.

## Branch nav group is Staff management
Branch portal sidebar group is Staff management (not Administrator). Contains Staff, Roles, Permissions, Shifts, Assign to shift. Platform keeps Administrator.

## Packs & Saves sits under Product management
Branch Product management lists Packs & Saves immediately after Products (Package2, manage-branch-products, /branch/{slug}/packs-saves). Do not wrap it in navSoon. Do not add a platform Packs & Saves menu.

## Pack offer is Amount off Percent or Pack price
Pack pricing is a 3-button row (Amount off / Percent / Pack price) on the combined line subtotal (qty × unit effective/selling), not per-product discount fields. Picker is this branch's stock only; duplicate SKUs are not allowed. Save is type=button until persist exists.
