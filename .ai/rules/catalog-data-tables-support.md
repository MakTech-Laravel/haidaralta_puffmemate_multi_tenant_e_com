---
paths:
    - 'app/{Models,Services/Inventory,Services/Catalog,DataTables,Support}/**'
---

# Catalog Data Tables Support

## Quantity is Stock; there is no branch_products table

A catalog-active product (products.status = true) is available at every branch. There is no branch_products table, BranchProduct model, availability service, or enable toggle route - do not reintroduce them.
inventories only carries quantity (no reserved_quantity, available_quantity, minimum_stock). InventoryService::adjust mutates quantity for stock_in / stock_out / adjustment; the reserve and sale enum cases exist for future orders and are not exposed in the UI.
Per-branch data is inventories + branch_product_prices only when `catalog.show_branch_pricing` is true. When that flag is false (default), catalog `products.selling_price` / `regular_price` / `discount_*` are the common ticket, compare-at, and discount for every branch (Manage pricing workspace); branch price UI, upserts, and price-history reports stay in the codebase but are hidden / abort 404. `buying_price` is last purchase cost (internal). `regular_price` is compare-at (shown when greater than selling and no live discount). Product discounts apply only to selling — via products.discount_* when branch pricing is off, or branch_product_prices when on. Lists show "Stock" from quantity and use BranchProductPriceDisplay::listDisplayForProductContext for selling vs effective (strikethrough selling when discounted; strikethrough regular when has_compare_at). `fixed` discount_price is amount off selling (`effective = selling − value`), not a replacement sale price. Never send buying_price to the storefront. There is no low-stock filter or badge.

## Ledger shows reason, signed Adjustment qty, and buying

Ledger rows expose reason / reason_label (em dash when null). Adjustment Qty may be a signed variance; Reduce Qty stays a positive units-removed count. New entry stores `unit_cost` (Buying column; em dash when null). Type labels come from InventoryTransactionType::label() / manualOptions() so DataTable, BranchProductShowPayload, and UI stay in sync.

## Ledger reason_label uses Other note

reason_label uses InventoryTransaction::reasonLabel() (typed note when Other). Payload includes reason_note. in/out rows stay null.

## BranchMap builds view-on-map URLs

BranchMap::links builds google, open_street_map, and OSM embed URLs from latitude/longitude. Platform branch show passes branch.map (null when no pin). View-on-map uses those URLs in BranchLocationMapDialog. Do not inline a live map on show.
