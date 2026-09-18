---
paths:
  - 'resources/js/components/stock/**'
  - resources/js/components/stock/manage-stock-page.tsx
---

# Stock

## Stock lives under Branch management on platform
Platform sidebar: Branch management → **Manage stock** (`stock/manage`). Product management keeps Categories and Products; Bulk Import appears only when `catalog.show_bulk_import` is true. Branch portal Product management lists Manage stock with Products (and Bulk Import when the flag is on). Daily stock, selling price, and discounts are edited on Manage stock after a branch+product pair is picked. Do not put Bulk Import or the global ledger on that page. Do not restore a top-level Stock management group or an Add staff sidebar shortcut. Stock transactions live under Reports (`reports/stock-transactions`). Do not restore `POST stock/manage`, Update price, a bulk Discounts workspace, or a `stock/transactions` redirect. Old `stock/price` and `/discounts` URLs still redirect through `ProductManageRedirect`.

## Manage uses single pair pickers
Manage stock is a picker-first workspace. Branch and product pickers are single-select `InfiniteScrollCombobox` against stock/*-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers, and do not reuse Import’s multi combobox. Omit `noneLabel` so a pair is required. Changing an unlocked picker `router.get`s the same page with `?branch=&product=` (skip if the query already matches). Branch portal hides the branch picker entirely (`lockBranch`); do not show a locked branch field. Catalog **Manage** with a known pair opens the same page with `locked=both`. No pair: pickers + empty prompt only. Pair resolved: **View details** (catalog product show) + Inventory & pricing. Do not inline Listing details on Manage. Do not restyle the Inventory & pricing card. The `{branch} products` header button truncates (`max-w-48` + `truncate` + `title`) so a long branch name cannot stretch the header.

## Import stock uses multi infinite pickers
Import stock is New entry only. Branch and product pickers are InfiniteScrollMultiCombobox against stock/*-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers. Quantity is an unsigned digit string with a stepper. Optional buying_price applies the same unit cost to every selected pair; omit it to leave unit_cost null. Branch portal hides the branch picker (`lockBranch`) and only posts this branch. Platform pages pass both option URLs; branch pages pass productOptionsUrl only.

## Stock pages share the pair query contract
Manage, Import, and the Stock transactions report share the query contract `product`, `branch`, `locked` (`product|branch|both`), resolved by `App\Support\StockPageContext`. Locked selections 404 when the id or slug does not resolve. The branch portal always forces `locked` to include `branch`. When a product is missing from a picker, Add product carries `return_to` = current URL; `CatalogReturnTo::afterProductCreate` redirects back with `?product={id}` appended. The report may pass All branches/products. Manage’s View transactions button uses the same `product` + `branch` query (`locked` omitted; no variant) on `reports/stock-transactions`.

## Truncate Manage header branch button
Manage stock header `{branch} products` button must truncate (max-w-48 + truncate + title with the full label). Never let a long branch name grow the header or wrap the row into a huge button.

## Branch portal hides branch pickers
On the branch portal, hide Branch entirely from Manage, Bulk Import, and the Stock transactions report (not a locked field). Search does not match branch names. Platform ledger rows are product name + SKU first, then branch, then type/reason/qty/buying; filters are Product then Branch.
