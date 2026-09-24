---
paths:
  - resources/js/components/branch/product-inventory-panel.tsx
  - 'resources/js/pages/{platform,branch}/**/products/show.tsx'
  - 'resources/js/pages/{platform,branch}/stock/manage.tsx'
  - 'resources/js/pages/{platform,branch}/catalog/pricing/manage.tsx'
  - resources/js/components/catalog/manage-catalog-pricing-page.tsx
  - app/Support/ProductManageRedirect.php
  - 'routes/{platform,branch}.php'
  - app/Support/BranchProductPriceDisplay.php
  - routes/platform.php
  - 'routes/*.php'
---

# Discounts

## Discounts live on Stock Manage or Manage pricing

There is no bulk Discounts workspace. When `catalog.show_branch_pricing` is true, buying, regular, selling, and discount type/value/dates are edited on Stock Manage’s Inventory & pricing card (`ProductInventoryPanel`) for one branch + one SKU (including variants). When the flag is false, Product management → Manage pricing edits catalog regular/selling + discount on `products` (no buying/stock/variants/history). Discount UI is a full-width bordered block with a 3-button type row (None / Amount off / Percent). Discounts apply only to selling_price. `fixed` is amount off (`effective = selling − discount_value`), not a sale price. Honor start/end dates in effective price (null dates = always on; outside the window = selling). Hydrate saved fixed/percent offers so Save is not destructive. Empty SKUs and prices with `discount_type` none/missing select None and hide value + dates.

## Price history is append-only

`branch_product_price_histories` is the operational trail (who, when, source, from/to). Spatie activity log remains generic. Skip no-op upserts. Do not put AuditsChanges on the history model. Coupons stay checkout-only (navSoon); do not add a Coupon model on the catalog SKU. Operators read history on `reports/price-history`, not as an inline card on Manage. `BranchProductShowPayload::product` does not include `price_history`.

## Old discount URLs redirect

GET/PUT `/discounts`, `/discounts/preview`, and legacy `/stock/discount` go through `App\Support\ProductManageRedirect`. Both IDs → `stock.manage` with query; platform product-only → Manage branches; otherwise products index. Branch portal product-only → that branch’s `stock.manage`. Empty still → products index (not unlocked manage). Do not restore DiscountController or `applyDiscount` / `previewSelectedPairs`.

## Retired stock URLs resolve through ProductManageRedirect

Old stock/price and /discounts URLs redirect here. Pair → `stock.manage` with query (not product show). Platform product-only → Manage branches. Empty → products index. Invalid filled ids 404. `GET stock/manage` is a real Inertia page again — do not send it through this helper.

## No stock/transactions redirect

Do not keep a stock/transactions redirect. The ledger URL is reports/stock-transactions only (platform.reports.stock-transactions / branch.reports.stock-transactions).

## Fixed discount is amount off

`discount_type` fixed is amount off selling: effective = max(0, selling_price − discount_price). Do not treat discount_price as the sale price. Percent stays selling × (1 − % / 100). Storefront pay price is effective; strikethrough is selling while the offer is live.

## Platform shift URLs split by owner
Platform-owned: /platform/shifts and /platform/shift-assignments (edit at /platform/shift-assignments/{id}/edit). Branch-owned: /platform/branch-shifts and /platform/branch-shift-assignments (edit at /platform/branch-shift-assignments/{id}/edit). Keep assign create off the /shifts prefix so the Shifts nav item is not also active. Assigned people lives at `/shifts/assigned-people` so Shifts stays active. There are no `/shifts/periods` URLs. Wayfinder needs separate controller classes for each URI set.

## Catalog discounts use product discount columns
When show_branch_pricing is false, listDisplayFromProduct / forCatalogProduct apply products.discount_* with the same live-window and fixed/percent math as branch prices. Lists strikethrough selling when discounted; otherwise compare-at regular when regular > selling. When the flag is true, discounts still come only from branch_product_prices.
