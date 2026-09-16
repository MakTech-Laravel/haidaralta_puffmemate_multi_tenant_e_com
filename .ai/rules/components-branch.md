---
paths:
  - 'resources/js/components/branch/**'
  - resources/js/components/branch/nav.ts
  - resources/js/components/branch/product-inventory-panel.tsx
  - resources/js/components/branch/branch-product-summary.tsx
---

# Components Branch

## Branch inventory panel scopes by variant
Branch/platform product show uses ProductInventoryPanel: base + variant scope selector, posts existing inventory/price routes with variant_id. Never edit global catalog metadata from branch. UI uses Card sections + shared `DatePicker` (`@/components/ui/date-picker`, Calendar popover, `yyyy-MM-dd`) — prefer over native `input type="date"`.

## Branch Catalog nav children
Inertia catalog pages live under `resources/js/pages/branch/catalog/{categories,products}` (same shape as platform). Categories routes have no destroy/restore. When catalog.branch_can_view_categories is false, the sidebar is a Products navLink (not a Catalog group). When true, Catalog lists Categories + Products. Keep the Categories item in source; wrap it, do not delete.

## Inventory panel is a compact listing card
Inventory manage is one `shadow-sm` card: snapshot strip (Stock, Selling, Effective) plus two content-sized Stock/Price forms with Save in each section header. Do not float a DemoStatCard, stretch equal-height footers, or leave an empty inventory column. DatePickers stay `min-w-0`. Type is a 3-button row, not a full-width select.

## Discount types + txn pagination filters
Pricing uses discount_type none|fixed|percent. No price, or `discount_type` none/missing, selects None and hides value+dates. Saved fixed/percent offers hydrate so Save is not destructive. Discount value label is Discount value (AUD) or (%), placeholders `e.g. 5.00` / `e.g. 10`. Transactions are server-paginated (`txn_page`) with `txn_scope` / `txn_from` / `txn_to` / `txn_type` — not a client-side limit(25) list and not `useDataTableQuery` (ledger must coexist with inventory/price forms). Reload only on Filter click, type change, or variant scope change — never on mount. Truncate ledger cells with DataTableTruncated.

## Saved selling price is always live
Do not show Price active. UpsertBranchProductPriceRequest has no `status`; BranchPricingService::upsert always persists `status => true`. Keep the DB column; list queries still use `where('status', true)`.

## Manage listing details match catalog product show
Branch/platform product manage uses one Listing details card (tile gallery rail + 3-col facts + category chips + tags + spec tiles). No Product details / Branch stock split, no header Avatar, no extra full-width gallery, no Branch network table. Platform category chips link to category show; branch chips link only when `catalog.branch_can_view_categories`.

## Inventory panel is quantity-only Stock
Inventory snapshot is { quantity } only (Stock). Do not post minimum_stock, reserved, available, or low-stock fields. Hide variant scope UI when catalog.show_product_variants is false. Keep adjust + price forms.

## Summary is listing details, not stock
Branch product summary is catalog listing details only. Stock and price live in ProductInventoryPanel. No enable toggle, low-stock badge, or available quantity.

## Inventory panel Adjustment is counted on-hand
Product inventory panel quantity is a clearable digit string, not Number(). Adjustment uses counted on-hand plus a required reason and the same live variance preview as stock import. Hide reason for New entry / Reduce. Disable Save when counted matches current stock. Transactions table shows reason_label (em dash when null) and may show a signed Adjustment qty.

## Inventory panel reason sits beside quantity
Adjustment reason select sits beside counted quantity. Other shows a required describe-the-reason input. Hide reason for New entry / Reduce. Ledger reason_label is the typed note when Other.
