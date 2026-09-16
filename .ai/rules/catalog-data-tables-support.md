---
paths:
  - 'app/{Models,Services/Inventory,Services/Catalog,DataTables,Support}/**'
---

# Catalog Data Tables Support

## Quantity is Stock; there is no branch_products table
A catalog-active product (products.status = true) is available at every branch. There is no branch_products table, BranchProduct model, availability service, or enable toggle route - do not reintroduce them.
inventories only carries quantity (no reserved_quantity, available_quantity, minimum_stock). InventoryService::adjust mutates quantity for stock_in / stock_out / adjustment; the reserve and sale enum cases exist for future orders and are not exposed in the UI.
Per-branch data is inventories + branch_product_prices only. Lists show "Stock" from quantity and use BranchProductPriceDisplay::listDisplay for selling vs effective price (strikethrough when discounted). There is no low-stock filter or badge.

## Ledger shows reason and signed Adjustment qty
Ledger rows expose reason / reason_label (em dash when null). Adjustment Qty may be a signed variance; Reduce Qty stays a positive units-removed count. Type labels come from InventoryTransactionType::label() / manualOptions() so DataTable, BranchProductShowPayload, and UI stay in sync.
