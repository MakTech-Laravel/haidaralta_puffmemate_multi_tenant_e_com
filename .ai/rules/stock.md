---
paths:
  - 'resources/js/components/stock/**'
---

# Stock

## Stock management is Manage, Bulk Import, and Transactions
Sidebar Stock management children are **Manage**, Bulk Import, Transactions. Group `href` is `stock/manage`. Daily stock, selling price, and discounts are edited on Manage (`GET stock/manage`) after a branch+product pair is picked. Do not put Bulk Import or the global ledger on Manage. Do not restore `POST stock/manage`, Update price, or a bulk Discounts workspace. Old `stock/price` and `/discounts` URLs still redirect through `ProductManageRedirect`.

## Manage uses single pair pickers
Manage stock is a picker-first workspace. Branch and product pickers are single-select `InfiniteScrollCombobox` against stock/*-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers, and do not reuse Import’s multi combobox. Omit `noneLabel` so a pair is required. Changing an unlocked picker `router.get`s the same page with `?branch=&product=` (skip if the query already matches). Branch portal locks the current branch. Catalog **Manage** with a known pair opens the same page with `locked=both`. No pair: pickers + empty prompt only. Pair resolved: **View details** (catalog product show) + Inventory & pricing. Do not inline Listing details on Manage. Do not restyle the Inventory & pricing card.

## Import stock uses multi infinite pickers
Import stock is New entry only. Branch and product pickers are InfiniteScrollMultiCombobox against stock/*-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers. Quantity is an unsigned digit string with a stepper. Branch portal locks the current branch. Platform pages pass both option URLs; branch pages pass productOptionsUrl only.

## Stock pages share the pair query contract
Manage, Import, and Stock transactions share the query contract `product`, `branch`, `locked` (`product|branch|both`), resolved by `App\Support\StockPageContext`. Locked selections 404 when the id or slug does not resolve. The branch portal always forces `locked` to include `branch`. When a product is missing from a picker, Add product carries `return_to` = current URL; `CatalogReturnTo::afterProductCreate` redirects back with `?product={id}` appended. Transactions may pass All branches/products. Manage’s View transactions button uses the same `product` + `branch` query (`locked` omitted; no variant).
