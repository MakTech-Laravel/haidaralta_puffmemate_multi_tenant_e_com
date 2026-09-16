---
paths:
  - 'resources/js/components/stock/**'
---

# Stock

## Stock tools are import and the ledger
Daily stock, selling price, and discounts are edited on the product manage Inventory & pricing card. Stock management in the sidebar is Bulk Import + Transactions only. Do not add Manage stock, Update price, or a bulk Discounts workspace. Old `stock/manage`, `stock/price`, and `/discounts` URLs redirect through `ProductManageRedirect`.

## Import stock uses multi infinite pickers
Import stock is New entry only. Branch and product pickers are InfiniteScrollMultiCombobox against stock/*-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers. Quantity is an unsigned digit string with a stepper. Branch portal locks the current branch. Platform pages pass both option URLs; branch pages pass productOptionsUrl only.

## Stock transactions keep the pair query contract
Import stock and Stock transactions share the query contract `product`, `branch`, `locked` (`product|branch|both`), resolved by `App\Support\StockPageContext`. Locked selections 404 when the id or slug does not resolve. The branch portal always forces `locked` to include `branch`. When a product is missing from a picker, Add product carries `return_to` = current URL; `CatalogReturnTo::afterProductCreate` redirects back with `?product={id}` appended. Transactions may pass All branches/products.

## Stock quantity is unsigned digits
Quantity fields accept digits only (empty while clearing, then 0–n). Type chooses direction: New entry adds, Reduce subtracts, Adjustment sets counted on-hand. Ledger may display a computed negative Adjustment variance; users never type a minus sign.
