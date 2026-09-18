---
paths:
  - resources/js/components/branch/product-inventory-panel.tsx
  - 'resources/js/pages/{platform,branch}/**/products/show.tsx'
  - 'resources/js/pages/{platform,branch}/stock/manage.tsx'
  - app/Support/ProductManageRedirect.php
  - 'routes/{platform,branch}.php'
  - app/Support/BranchProductPriceDisplay.php
  - routes/platform.php
---

# Discounts

## Discounts live on Stock Manage
There is no bulk Discounts workspace. Buying, regular, selling, and discount type/value/dates are edited on Stock Manage’s Inventory & pricing card (`ProductInventoryPanel`) for one branch + one SKU (including variants). Discount lives in a full-width bordered block below the Stock and Price sections, with a 3-button type row (None / Amount off / Percent). Discounts apply only to selling_price. `fixed` is amount off (`effective = selling − discount_value`), not a sale price. Honor start/end dates in effective price (null dates = always on; outside the window = selling). Hydrate saved fixed/percent offers so Save is not destructive. Empty SKUs and prices with `discount_type` none/missing select None and hide value + dates.

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
