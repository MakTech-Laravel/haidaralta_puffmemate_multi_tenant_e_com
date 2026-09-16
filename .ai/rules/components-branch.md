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

## Inventory panel matches dashboard cards
Inventory manage UI matches branch dashboard: DemoStatCard metrics, single split Card for inventory+pricing (equal-height footers), shadow-none cards, tables without nested bordered wrappers.

## Discount types + txn pagination filters
Pricing uses discount_type none|fixed|percent; when none, hide/clear value+dates. Transactions are server-paginated (txn_page) with txn_scope/txn_from/txn_to filters — not a client-side limit(25) list.

## Inventory panel is quantity-only Stock
Inventory snapshot is { quantity } only (Stock). Do not post minimum_stock, reserved, available, or low-stock fields. Hide variant scope UI when catalog.show_product_variants is false. Keep adjust + price forms.

## Summary shows stock and missing price
Branch product summary shows quantity (Stock) and missing_price only. No enable toggle, low-stock badge, or available quantity.

## Inventory panel Adjustment is counted on-hand
Product inventory panel quantity is a clearable digit string, not Number(). Adjustment uses counted on-hand plus a required reason and the same live variance preview as Manage stock. Hide reason for New entry / Reduce. Disable Save when counted matches current stock. Transactions table shows reason_label (em dash when null) and may show a signed Adjustment qty.
